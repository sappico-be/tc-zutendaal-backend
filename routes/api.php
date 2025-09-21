<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Http\Controllers\Api\Admin\NewsController;
use App\Http\Controllers\Api\Admin\EventController;
use App\Http\Controllers\Api\Admin\PaymentController;
use App\Http\Controllers\Api\Admin\LessonController;

/*
|--------------------------------------------------------------------------
| API Routes for TC Zutendaal
|--------------------------------------------------------------------------
*/

// Test route
Route::get('/test', function () {
    return response()->json([
        'message' => 'TC Zutendaal API works!',
        'laravel_version' => app()->version(),
        'timestamp' => now()
    ]);
});

// Authentication endpoint
Route::post('/auth/login', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required|string',
    ]);
    
    $user = User::where('email', $request->email)->first();
    
    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json([
            'errors' => [
                'email' => ['De opgegeven inloggegevens zijn onjuist.'],
            ]
        ], 422);
    }
    
    // Update last login
    $user->updateLastLogin();
    
    // Create new token (zonder oude tokens te verwijderen voor nu)
    $token = $user->createToken('auth-token')->plainTextToken;
    
    // Determine abilities based on role
    $abilities = [];
    switch ($user->role) {
        case 'admin':
            $abilities = [
                ['action' => 'manage', 'subject' => 'all'],
            ];
            break;
        case 'board_member':
            $abilities = [
                ['action' => 'manage', 'subject' => 'news'],
                ['action' => 'manage', 'subject' => 'events'],
                ['action' => 'manage', 'subject' => 'members'],
                ['action' => 'read', 'subject' => 'payments'],
            ];
            break;
        case 'trainer':
            $abilities = [
                ['action' => 'manage', 'subject' => 'events'],
                ['action' => 'read', 'subject' => 'members'],
                ['action' => 'create', 'subject' => 'news'],
            ];
            break;
        default:
            $abilities = [
                ['action' => 'read', 'subject' => 'own-profile'],
                ['action' => 'read', 'subject' => 'events'],
            ];
    }
    
    // Return response in Vuexy format
    return response()->json([
        'accessToken' => $token,
        'userData' => [
            'id' => $user->id,
            'fullName' => $user->full_name ?? $user->name,
            'username' => explode('@', $user->email)[0],
            'avatar' => $user->avatar ?? '/images/avatars/avatar-1.png',
            'email' => $user->email,
            'role' => $user->role,
            'memberNumber' => $user->member_number,
            'membershipType' => $user->membership_type,
        ],
        'userAbilityRules' => $abilities,
    ]);
});

// Logout endpoint
Route::post('/auth/logout', function (Request $request) {
    if ($request->user()) {
        $request->user()->currentAccessToken()->delete();
    }
    
    return response()->json(['message' => 'Successfully logged out']);
})->middleware('auth:sanctum');

// Get current user
Route::get('/auth/user', function (Request $request) {
    $user = $request->user();
    
    // Determine abilities based on role
    $abilities = [];
    switch ($user->role) {
        case 'admin':
            $abilities = [
                ['action' => 'manage', 'subject' => 'all'],
            ];
            break;
        case 'board_member':
            $abilities = [
                ['action' => 'manage', 'subject' => 'news'],
                ['action' => 'manage', 'subject' => 'events'],
                ['action' => 'manage', 'subject' => 'members'],
                ['action' => 'read', 'subject' => 'payments'],
            ];
            break;
        case 'trainer':
            $abilities = [
                ['action' => 'manage', 'subject' => 'events'],
                ['action' => 'read', 'subject' => 'members'],
                ['action' => 'create', 'subject' => 'news'],
            ];
            break;
        default:
            $abilities = [
                ['action' => 'read', 'subject' => 'own-profile'],
                ['action' => 'read', 'subject' => 'events'],
            ];
    }
    
    return response()->json([
        'userData' => [
            'id' => $user->id,
            'fullName' => $user->full_name ?? $user->name,
            'username' => explode('@', $user->email)[0],
            'avatar' => $user->avatar ?? '/images/avatars/avatar-1.png',
            'email' => $user->email,
            'role' => $user->role,
            'memberNumber' => $user->member_number,
            'membershipType' => $user->membership_type,
        ],
        'userAbilityRules' => $abilities,
    ]);
})->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| Admin API Routes (Protected)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->group(function () {
    
    // Dashboard Statistics
    Route::get('/dashboard/stats', function() {
        $stats = [
            'totalMembers' => \App\Models\User::where('membership_type', '!=', 'non_member')->count(),
            'activeMembers' => \App\Models\User::activeMembers()->count(),
            'upcomingEvents' => \App\Models\Event::upcoming()->count(),
            'recentRegistrations' => \App\Models\EventRegistration::where('created_at', '>', now()->subDays(7))->count(),
            'monthlyRevenue' => \App\Models\EventRegistration::where('payment_status', 'paid')
                ->where('paid_at', '>', now()->startOfMonth())
                ->sum('amount_paid'),
            'latestNews' => \App\Models\NewsArticle::published()
                ->latest('published_at')
                ->take(5)
                ->get(['id', 'title', 'slug', 'published_at', 'views']),
            'upcomingEventsList' => \App\Models\Event::upcoming()
                ->take(5)
                ->withCount('confirmedRegistrations')
                ->get(['id', 'title', 'slug', 'start_date', 'type', 'max_participants']),
            'recentMembers' => \App\Models\User::where('membership_type', '!=', 'non_member')
                ->latest('member_since')
                ->take(5)
                ->get(['id', 'name', 'email', 'member_number', 'membership_type', 'member_since']),
        ];
        
        return response()->json($stats);
    });
    
    // News Management
    Route::prefix('news')->controller(NewsController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{id}', 'show');
        Route::put('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
        Route::post('/bulk-delete', 'bulkDelete');
        Route::patch('/{id}/status', 'updateStatus');
    });
    
    // Event Management
    Route::prefix('events')->controller(EventController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/statistics', 'statistics');
        Route::get('/{id}', 'show');
        Route::put('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
        
        // Event Registrations
        Route::get('/{id}/registrations', 'registrations');
        Route::post('/{id}/registrations', 'addRegistration');
        Route::patch('/{eventId}/registrations/{registrationId}', 'updateRegistration');
    });

    // Lesson Management
    Route::prefix('lessons')->controller(\App\Http\Controllers\Api\Admin\LessonController::class)->group(function () {
        // Packages
        Route::get('/packages', 'indexPackages');
        Route::post('/packages', 'storePackage');
        Route::get('/packages/{id}', 'showPackage');
        Route::put('/packages/{id}', 'updatePackage');
        
        // Registrations
        Route::get('/packages/{id}/registrations', 'getRegistrations');
        
        // Groups - COMPLETE set of routes
        Route::post('/packages/{id}/groups', 'storeGroup');
        Route::put('/packages/{packageId}/groups/{groupId}', 'updateGroup'); // UPDATE route
        Route::delete('/packages/{packageId}/groups/{groupId}', 'deleteGroup'); // DELETE route
        Route::post('/packages/{packageId}/groups/{groupId}/assign', 'assignToGroup');
        Route::post('/packages/{packageId}/remove-from-group', 'removeFromGroup');
        
        // Locations
        Route::get('/locations', 'getLocations');
        Route::post('/locations', 'storeLocation');

        // Schedule Management
        Route::post('/packages/{packageId}/groups/{groupId}/schedule', 'generateSchedule');
        Route::get('/packages/{packageId}/groups/{groupId}/schedule', 'getGroupSchedule');
        Route::put('/packages/{packageId}/groups/{groupId}/schedule/{scheduleId}', 'updateSchedule');
        Route::post('/packages/{packageId}/groups/{groupId}/schedule/{scheduleId}/cancel', 'cancelLesson');
        Route::get('/packages/{packageId}/calendar', 'getCalendar');

        // Trainer Availability
        Route::post('/packages/{packageId}/trainer-availability', 'setTrainerAvailability');
        Route::get('/packages/{packageId}/trainer-availability/{trainerId}', 'getTrainerAvailability');
        Route::get('/packages/{packageId}/available-trainers', 'getAvailableTrainers');

        // Attendance Management
        Route::get('/packages/{packageId}/groups/{groupId}/schedule/{scheduleId}/attendance', 'getLessonAttendance');
        Route::post('/packages/{packageId}/groups/{groupId}/schedule/{scheduleId}/attendance', 'updateAttendance');
        Route::post('/packages/{packageId}/groups/{groupId}/schedule/{scheduleId}/attendance/mark', 'markAttendance');

        // Attendance Statistics
        Route::get('/packages/{packageId}/users/{userId}/attendance-stats', 'getUserAttendanceStats');
        Route::get('/packages/{packageId}/groups/{groupId}/attendance-stats', 'getGroupAttendanceStats');
        Route::get('/packages/{packageId}/attendance-stats', 'getPackageAttendanceStats');
        
        // Notifications
        Route::post('/packages/{packageId}/notifications', 'sendNotification');
        Route::get('/packages/{packageId}/notifications', 'getNotifications');
        Route::delete('/packages/{packageId}/notifications/{notificationId}', 'cancelNotification');

        // Reminder Settings
        Route::get('/packages/{packageId}/reminder-settings', 'reminderSettings');
        Route::post('/packages/{packageId}/reminder-settings', 'reminderSettings');
        Route::post('/packages/{packageId}/test-notification', 'sendTestNotification');

        Route::get('/packages/{packageId}/financial-report', 'getFinancialReport');
        Route::post('/registrations/{registrationId}/mark-paid', 'markRegistrationAsPaid');
        Route::post('/packages/{packageId}/send-payment-reminders', 'sendPaymentReminders');
        Route::post('/registrations/{registrationId}/send-reminder', 'sendIndividualPaymentReminder');
        Route::get('/packages/{packageId}/export-financial-report', 'exportFinancialReport');
    });

    // Trainer Hours Management
    Route::prefix('trainer-hours')->controller(\App\Http\Controllers\Api\Admin\TrainerHoursController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::put('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
        
        // Approval
        Route::post('/{id}/approve', 'approve')->middleware('admin');
        Route::post('/{id}/reject', 'reject')->middleware('admin');
        Route::post('/bulk-approve', 'bulkApprove')->middleware('admin');
        
        // Import & Export
        Route::post('/import-from-schedule', 'importFromSchedule');
        Route::get('/export', 'exportHours');
        
        // Monthly Summary
        Route::get('/monthly-summary', 'getMonthlySummary');
        Route::post('/submit-monthly', 'submitMonthly');
        
        // Contracts
        Route::get('/contracts', 'getContracts');
        Route::post('/contracts', 'storeContract')->middleware('admin');
        
        // Payroll
        Route::get('/payroll', 'getPayrollOverview')->middleware('admin');
        Route::post('/summaries/{summaryId}/mark-paid', 'markAsPaid')->middleware('admin');
    });

    // Trainers endpoint (voeg dit toe buiten de lessons prefix)
    Route::get('/trainers', function() {
        $trainers = \App\Models\User::whereIn('role', ['trainer', 'admin', 'board_member'])
            ->where('is_active', true)
            ->get(['id', 'name', 'email', 'role']);
        
        return response()->json([
            'success' => true,
            'data' => $trainers
        ]);
    });
    
    // Member Management
    Route::prefix('members')->group(function () {
        Route::get('/', function(Request $request) {
            $query = \App\Models\User::query();
            
            // Filter by membership type
            if ($request->has('membership_type')) {
                $query->where('membership_type', $request->membership_type);
            }
            
            // Filter by active status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }
            
            // Search
            if ($request->has('q')) {
                $search = $request->q;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('member_number', 'like', "%{$search}%")
                      ->orWhere('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%");
                });
            }
            
            // Pagination
            $perPage = $request->get('perPage', 10);
            $page = $request->get('page', 1);
            
            $members = $query->paginate($perPage, ['*'], 'page', $page);
            
            return response()->json([
                'members' => $members->items(),
                'total' => $members->total(),
                'page' => $members->currentPage(),
                'totalPages' => $members->lastPage(),
            ]);
        });
        
        Route::get('/{id}', function($id) {
            $member = \App\Models\User::with(['eventRegistrations.event', 'payments'])
                ->findOrFail($id);
            
            return response()->json($member);
        });
        
        Route::put('/{id}', function(Request $request, $id) {
            $member = \App\Models\User::findOrFail($id);
            
            $validated = $request->validate([
                'first_name' => 'nullable|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $id,
                'phone' => 'nullable|string|max:20',
                'mobile' => 'nullable|string|max:20',
                'membership_type' => 'nullable|in:junior,senior,veteran,honorary,non_member',
                'is_active' => 'nullable|boolean',
                'tennis_level' => 'nullable|numeric|min:1|max:9',
                'can_book_courts' => 'nullable|boolean',
                'receives_newsletter' => 'nullable|boolean',
            ]);
            
            $member->update($validated);
            
            return response()->json([
                'message' => 'Lid succesvol bijgewerkt',
                'member' => $member
            ]);
        });
    });
    
    // Payment Management (basic for now)
    Route::prefix('payments')->group(function () {
        Route::get('/', function(Request $request) {
            $query = \App\Models\Payment::with(['user', 'payable']);
            
            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            
            // Date range
            if ($request->has('from')) {
                $query->where('created_at', '>=', $request->from);
            }
            if ($request->has('to')) {
                $query->where('created_at', '<=', $request->to);
            }
            
            $payments = $query->latest()->paginate($request->get('perPage', 20));
            
            return response()->json([
                'payments' => $payments->items(),
                'total' => $payments->total(),
                'totalAmount' => $query->sum('amount'),
            ]);
        });
    });

    // Payment Management (update de bestaande met de controller)
    Route::prefix('payments')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\Admin\PaymentController::class, 'index']);
    });
});

/*
|--------------------------------------------------------------------------
| Public API Routes (No Authentication)
|--------------------------------------------------------------------------
*/
Route::prefix('public')->group(function () {
    
    // Public news
    Route::get('/news', function(Request $request) {
        $news = \App\Models\NewsArticle::published()
            ->latest('published_at')
            ->paginate($request->get('per_page', 10));
            
        return response()->json($news);
    });
    
    Route::get('/news/{slug}', function($slug) {
        $article = \App\Models\NewsArticle::where('slug', $slug)
            ->published()
            ->firstOrFail();
        
        $article->incrementViews();
        
        return response()->json($article);
    });
    
    // Public events
    Route::get('/events', function(Request $request) {
        $events = \App\Models\Event::upcoming()
            ->where('status', 'published')
            ->withCount('confirmedRegistrations')
            ->paginate($request->get('per_page', 10));
            
        return response()->json($events);
    });
    
    Route::get('/events/{slug}', function($slug) {
        $event = \App\Models\Event::where('slug', $slug)
            ->where('status', 'published')
            ->withCount('confirmedRegistrations')
            ->firstOrFail();
            
        return response()->json($event);
    });
    
    // Event registration (for members via public site)
    Route::post('/events/{id}/register', function(Request $request, $id) {
        // This would be used from your React frontend
        $event = \App\Models\Event::findOrFail($id);
        
        $validated = $request->validate([
            'email' => 'required|email',
            'name' => 'required|string',
            'phone' => 'required|string',
        ]);
        
        // Find or create user
        $user = \App\Models\User::where('email', $validated['email'])->first();
        
        if (!$user) {
            $user = \App\Models\User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => Hash::make(Str::random(16)), // Random password
                'membership_type' => 'non_member',
            ]);
        }
        
        // Check if already registered
        if ($event->registrations()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'error' => 'Je bent al ingeschreven voor dit evenement'
            ], 422);
        }
        
        // Create registration
        $registration = \App\Models\EventRegistration::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'payment_status' => 'unpaid',
        ]);
        
        // Here you would initiate payment process
        // For now, just return success
        
        return response()->json([
            'message' => 'Inschrijving succesvol',
            'registration_id' => $registration->id,
            'payment_required' => $event->price_members > 0,
            'amount' => $user->membership_type !== 'non_member' 
                ? $event->price_members 
                : $event->price_non_members,
        ]);
    });
});

// Public payment routes (moet buiten auth middleware voor Mollie webhook)
Route::post('/events/{id}/register-and-pay', [App\Http\Controllers\Api\Admin\PaymentController::class, 'createPayment']);
Route::post('/webhooks/mollie', [App\Http\Controllers\Api\Admin\PaymentController::class, 'webhook'])->name('webhooks.mollie');
Route::get('/payment/success/{registrationId}', [App\Http\Controllers\Api\Admin\PaymentController::class, 'success']);
