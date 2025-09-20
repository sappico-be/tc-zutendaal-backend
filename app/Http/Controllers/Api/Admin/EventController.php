<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    /**
     * Display a listing of events
     */
    public function index(Request $request)
    {
        $query = Event::with(['creator', 'registrations']);

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->where('start_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('end_date', '<=', $request->date_to);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        // Add registration counts
        $query->withCount([
            'registrations',
            'registrations as confirmed_registrations_count' => function($q) {
                $q->where('status', 'confirmed');
            },
            'registrations as paid_registrations_count' => function($q) {
                $q->where('payment_status', 'paid');
            }
        ]);

        // Sorting
        $sortBy = $request->get('sort_by', 'start_date');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 10);
        $events = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $events->items(),
            'meta' => [
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
            ]
        ]);
    }

    /**
     * Store a newly created event
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'content' => 'nullable|string',
            'type' => 'required|in:tournament,training,social,meeting,other',
            'location' => 'nullable|string|max:255',
            'start_date' => 'required|date|after:now',
            'end_date' => 'required|date|after_or_equal:start_date',
            'registration_deadline' => 'nullable|date|before:start_date',
            'max_participants' => 'nullable|integer|min:1',
            'min_participants' => 'nullable|integer|min:1',
            'price_members' => 'required|numeric|min:0',
            'price_non_members' => 'nullable|numeric|min:0',
            'members_only' => 'boolean',
            'status' => 'required|in:draft,published,cancelled',
            'featured_image' => 'nullable|image|max:2048',
            'settings' => 'nullable|array',
        ]);

        // Handle image upload
        if ($request->hasFile('featured_image')) {
            $path = $request->file('featured_image')->store('events', 'public');
            $validated['featured_image'] = $path;
        }

        // Generate slug
        $validated['slug'] = Str::slug($validated['title']);
        
        // Check for duplicate slug
        $originalSlug = $validated['slug'];
        $count = 1;
        while (Event::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $originalSlug . '-' . $count;
            $count++;
        }

        // Set creator
        $validated['created_by'] = auth()->id();

        $event = Event::create($validated);
        $event->load('creator');

        return response()->json([
            'success' => true,
            'message' => 'Evenement succesvol aangemaakt',
            'data' => $event
        ], 201);
    }

    /**
     * Display the specified event with registrations
     */
    public function show($id)
    {
        $event = Event::with([
            'creator',
            'registrations.user',
            'registrations' => function($q) {
                $q->orderBy('created_at', 'desc');
            }
        ])
        ->withCount([
            'registrations',
            'registrations as confirmed_registrations_count' => function($q) {
                $q->where('status', 'confirmed');
            }
        ])
        ->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $event
        ]);
    }

    /**
     * Update the specified event
     */
    public function update(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'content' => 'nullable|string',
            'type' => 'sometimes|required|in:tournament,training,social,meeting,other',
            'location' => 'nullable|string|max:255',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after_or_equal:start_date',
            'registration_deadline' => 'nullable|date|before:start_date',
            'max_participants' => 'nullable|integer|min:1',
            'min_participants' => 'nullable|integer|min:1',
            'price_members' => 'sometimes|required|numeric|min:0',
            'price_non_members' => 'nullable|numeric|min:0',
            'members_only' => 'boolean',
            'status' => 'sometimes|required|in:draft,published,cancelled,completed',
            'featured_image' => 'nullable|image|max:2048',
            'settings' => 'nullable|array',
        ]);

        // Handle image upload
        if ($request->hasFile('featured_image')) {
            // Delete old image if exists
            if ($event->featured_image) {
                Storage::disk('public')->delete($event->featured_image);
            }
            $path = $request->file('featured_image')->store('events', 'public');
            $validated['featured_image'] = $path;
        }

        // Update slug if title changed
        if (isset($validated['title']) && $validated['title'] !== $event->title) {
            $validated['slug'] = Str::slug($validated['title']);
            
            // Check for duplicate slug
            $originalSlug = $validated['slug'];
            $count = 1;
            while (Event::where('slug', $validated['slug'])->where('id', '!=', $id)->exists()) {
                $validated['slug'] = $originalSlug . '-' . $count;
                $count++;
            }
        }

        $event->update($validated);
        $event->load('creator');

        return response()->json([
            'success' => true,
            'message' => 'Evenement succesvol bijgewerkt',
            'data' => $event
        ]);
    }

    /**
     * Remove the specified event
     */
    public function destroy($id)
    {
        $event = Event::findOrFail($id);
        
        // Check if event has registrations
        if ($event->registrations()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Kan evenement niet verwijderen omdat er al inschrijvingen zijn'
            ], 422);
        }
        
        // Delete image if exists
        if ($event->featured_image) {
            Storage::disk('public')->delete($event->featured_image);
        }
        
        $event->delete();

        return response()->json([
            'success' => true,
            'message' => 'Evenement succesvol verwijderd'
        ]);
    }

    /**
     * Get event registrations
     */
    public function registrations($id)
    {
        $event = Event::findOrFail($id);
        
        $registrations = EventRegistration::where('event_id', $id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $registrations,
            'summary' => [
                'total' => $registrations->count(),
                'confirmed' => $registrations->where('status', 'confirmed')->count(),
                'pending' => $registrations->where('status', 'pending')->count(),
                'cancelled' => $registrations->where('status', 'cancelled')->count(),
                'paid' => $registrations->where('payment_status', 'paid')->count(),
                'unpaid' => $registrations->where('payment_status', 'unpaid')->count(),
                'total_revenue' => $registrations->sum('amount_paid'),
            ]
        ]);
    }

    /**
     * Update registration status
     */
    public function updateRegistration(Request $request, $eventId, $registrationId)
    {
        $registration = EventRegistration::where('event_id', $eventId)
            ->where('id', $registrationId)
            ->firstOrFail();

        $validated = $request->validate([
            'status' => 'sometimes|in:pending,confirmed,cancelled,waitlist',
            'payment_status' => 'sometimes|in:unpaid,partially_paid,paid,refunded',
            'amount_paid' => 'sometimes|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if (isset($validated['payment_status']) && $validated['payment_status'] === 'paid') {
            $validated['paid_at'] = now();
        }

        $registration->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Inschrijving succesvol bijgewerkt',
            'data' => $registration->load('user')
        ]);
    }

    /**
     * Add manual registration
     */
    public function addRegistration(Request $request, $eventId)
    {
        $event = Event::findOrFail($eventId);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'status' => 'required|in:pending,confirmed,waitlist',
            'payment_status' => 'required|in:unpaid,paid',
            'amount_paid' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        // Check if user already registered
        if ($event->registrations()->where('user_id', $validated['user_id'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Deze gebruiker is al ingeschreven voor dit evenement'
            ], 422);
        }

        // Check max participants
        if ($event->max_participants && $event->confirmedRegistrations()->count() >= $event->max_participants) {
            $validated['status'] = 'waitlist';
        }

        $validated['event_id'] = $eventId;
        if ($validated['payment_status'] === 'paid') {
            $validated['paid_at'] = now();
        }

        $registration = EventRegistration::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Inschrijving succesvol toegevoegd',
            'data' => $registration->load('user')
        ], 201);
    }

    /**
     * Get event statistics
     */
    public function statistics()
    {
        $stats = [
            'total_events' => Event::count(),
            'upcoming_events' => Event::upcoming()->count(),
            'past_events' => Event::past()->count(),
            'total_registrations' => EventRegistration::count(),
            'total_revenue' => EventRegistration::where('payment_status', 'paid')->sum('amount_paid'),
            'events_by_type' => Event::select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->pluck('count', 'type'),
            'monthly_events' => Event::whereYear('start_date', now()->year)
                ->select(DB::raw('EXTRACT(MONTH FROM start_date) as month'), DB::raw('count(*) as count'))
                ->groupBy('month')
                ->orderBy('month')
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
