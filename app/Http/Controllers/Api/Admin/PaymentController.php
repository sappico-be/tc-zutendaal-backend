<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Mail\EventRegistrationConfirmed;
use Illuminate\Support\Facades\Mail;

class PaymentController extends Controller
{
    /**
     * Create a payment for event registration
     */
    public function createPayment(Request $request, $eventId)
    {
        $event = Event::findOrFail($eventId);
        
        // Validatie
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|string',
        ]);
        
        // Maak of vind user
        $user = User::firstOrCreate(
            ['email' => $validated['email']],
            [
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'password' => bcrypt(Str::random(16)),
                'membership_type' => 'non_member',
            ]
        );
        
        // Check if already registered
        if ($event->registrations()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'success' => false,
                'error' => 'Je bent al ingeschreven voor dit evenement'
            ], 422);
        }
        
        // Bepaal prijs
        $amount = $user->membership_type !== 'non_member' 
            ? $event->price_members 
            : ($event->price_non_members ?? $event->price_members);
        
        // Als bedrag 0 is, geen payment nodig
        if ($amount <= 0) {
            $registration = EventRegistration::create([
                'event_id' => $event->id,
                'user_id' => $user->id,
                'status' => 'confirmed',
                'payment_status' => 'paid',
                'amount_paid' => 0,
            ]);
            
            return response()->json([
                'success' => true,
                'registration_id' => $registration->id,
                'payment_required' => false,
            ]);
        }
        
        // Maak registratie
        $registration = EventRegistration::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'payment_status' => 'unpaid',
        ]);
        
        try {
            // Maak Mollie payment
            $mollie = new \Mollie\Api\MollieApiClient();
            $mollie->setApiKey(config('mollie.key'));
            
            $payment = $mollie->payments->create([
                'amount' => [
                    'currency' => 'EUR',
                    'value' => number_format($amount, 2, '.', ''),
                ],
                'description' => "Inschrijving: {$event->title}",
                'redirectUrl' => config('app.url') . "/tennis/payments/success/{$registration->id}",
                'webhookUrl' => url(config('mollie.webhook_url')),
                'metadata' => [
                    'registration_id' => $registration->id,
                    'event_id' => $event->id,
                ],
            ]);
            
            // Sla payment ID op
            Payment::create([
                'transaction_id' => $payment->id,
                'payable_type' => EventRegistration::class,
                'payable_id' => $registration->id,
                'user_id' => $user->id,
                'amount' => $amount,
                'status' => 'pending',
                'provider' => 'mollie',
                'provider_payment_id' => $payment->id,
            ]);
            
            return response()->json([
                'success' => true,
                'payment_url' => $payment->getCheckoutUrl(),
                'registration_id' => $registration->id,
            ]);
            
        } catch (\Exception $e) {
            // Bij error, verwijder registratie
            $registration->delete();
            
            return response()->json([
                'success' => false,
                'error' => 'Er ging iets mis bij het aanmaken van de betaling: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Handle Mollie webhook
     */
    public function webhook(Request $request)
    {
        try {
            $mollie = new \Mollie\Api\MollieApiClient();
            $mollie->setApiKey(config('mollie.key'));
            
            $payment = $mollie->payments->get($request->id);
            
            $localPayment = Payment::where('provider_payment_id', $payment->id)->first();
            
            if (!$localPayment) {
                return response('Payment not found', 404);
            }
            
            if ($payment->isPaid()) {
                $localPayment->markAsCompleted();
                
                // Update registration
                $registration = $localPayment->payable;
                $registration->update([
                    'status' => 'confirmed',
                    'payment_status' => 'paid',
                    'amount_paid' => $localPayment->amount,
                    'paid_at' => now(),
                ]);
                
                // Verstuur bevestigingsmail
                $registration->load(['event', 'user']);
                Mail::to($registration->user->email)->send(new EventRegistrationConfirmed($registration));
            } elseif ($payment->isFailed()) {
                $localPayment->markAsFailed();
                
                // Update registration
                $registration = $localPayment->payable;
                $registration->update([
                    'payment_status' => 'failed',
                ]);
            } elseif ($payment->isCanceled()) {
                $localPayment->update(['status' => 'cancelled']);
                
                // Update registration
                $registration = $localPayment->payable;
                $registration->update([
                    'status' => 'cancelled',
                ]);
            }
            
            return response('OK', 200);
            
        } catch (\Exception $e) {
            \Log::error('Mollie webhook error: ' . $e->getMessage());
            return response('Error', 500);
        }
    }
    
    /**
     * Payment success page
     */
    public function success($registrationId)
    {
        $registration = EventRegistration::with(['event', 'user'])->findOrFail($registrationId);
        
        return view('payment.success', compact('registration'));
    }
    
    /**
     * Get all payments (admin)
     */
    public function index(Request $request)
    {
        $query = Payment::with(['user', 'payable']);
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('from')) {
            $query->where('created_at', '>=', $request->from);
        }
        
        if ($request->has('to')) {
            $query->where('created_at', '<=', $request->to);
        }
        
        $payments = $query->latest()->paginate($request->get('per_page', 20));
        
        return response()->json([
            'success' => true,
            'data' => $payments->items(),
            'meta' => [
                'total' => $payments->total(),
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
            ],
        ]);
    }
}
