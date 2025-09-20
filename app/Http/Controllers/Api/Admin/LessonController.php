<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\LessonPackage;
use App\Models\LessonRegistration;
use App\Models\LessonGroup;
use App\Models\LessonLocation;
use App\Models\LessonSchedule;
use App\Models\TrainerAvailability;
use App\Models\LessonAttendance;
use App\Mail\LessonReminder;
use App\Mail\LessonCancelled;
use App\Mail\LessonChanged;
use Illuminate\Support\Facades\Mail;
use App\Models\LessonNotification;
use Illuminate\Http\Request;
use Carbon\Carbon;

class LessonController extends Controller
{
    // Lesson Packages
    public function indexPackages()
    {
        $packages = LessonPackage::withCount(['registrations', 'groups'])
            ->latest()
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => $packages
        ]);
    }
    
    public function storePackage(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'total_lessons' => 'required|integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'registration_deadline' => 'required|date|before:start_date',
            'price_members' => 'required|numeric|min:0',
            'price_non_members' => 'nullable|numeric|min:0',
            'min_participants' => 'nullable|integer|min:1',
            'max_participants' => 'nullable|integer|min:1',
            'available_days' => 'required|array',
            'status' => 'required|in:draft,open,closed,completed',
        ]);
        
        $package = LessonPackage::create($validated);
        
        return response()->json([
            'success' => true,
            'data' => $package
        ]);
    }
    
    public function showPackage($id)
    {
        $package = LessonPackage::with([
            'registrations.user', 
            'groups.trainer', 
            'groups.location',
            'groups.registrations.user', // <-- Deze toevoegen
            'trainerAvailabilities.trainer'
        ])
        ->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $package
        ]);
    }

    public function updatePackage(Request $request, $id)
    {
        $package = LessonPackage::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'total_lessons' => 'required|integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'registration_deadline' => 'required|date|before:start_date',
            'price_members' => 'required|numeric|min:0',
            'price_non_members' => 'nullable|numeric|min:0',
            'min_participants' => 'nullable|integer|min:1',
            'max_participants' => 'nullable|integer|min:1',
            'available_days' => 'required|array',
            'status' => 'required|in:draft,open,closed,completed',
        ]);
        
        $package->update($validated);
        
        return response()->json([
            'success' => true,
            'data' => $package
        ]);
    }
    
    // Registrations
    public function getRegistrations($packageId)
    {
        $registrations = LessonRegistration::where('lesson_package_id', $packageId)
            ->with(['user', 'assignedGroup'])
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => $registrations
        ]);
    }

    // Groups
    public function storeGroup(Request $request, $packageId)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'level' => 'required|in:beginner,intermediate,advanced',
            'max_participants' => 'required|integer|min:1',
            'trainer_id' => 'nullable|exists:users,id',
            'location_id' => 'nullable|exists:lesson_locations,id',
            'schedule_days' => 'nullable|array',
            'default_start_time' => 'nullable',
            'default_end_time' => 'nullable',
        ]);
        
        $validated['lesson_package_id'] = $packageId;
        
        $group = LessonGroup::create($validated);
        
        return response()->json([
            'success' => true,
            'data' => $group->load('trainer', 'location')
        ]);
    }

    // Update een bestaande groep
    public function updateGroup(Request $request, $packageId, $groupId)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'level' => 'required|in:beginner,intermediate,advanced',
            'max_participants' => 'required|integer|min:1',
            'trainer_id' => 'nullable|exists:users,id',
            'location_id' => 'nullable|exists:lesson_locations,id',
            'schedule_days' => 'nullable|array',
            'default_start_time' => 'nullable',
            'default_end_time' => 'nullable',
        ]);
        
        $group = LessonGroup::where('lesson_package_id', $packageId)
            ->where('id', $groupId)
            ->firstOrFail();
        
        $group->update($validated);
        
        return response()->json([
            'success' => true,
            'data' => $group->load('trainer', 'location', 'registrations.user')
        ]);
    }

    // Verwijder een groep
    public function deleteGroup($packageId, $groupId)
    {
        $group = LessonGroup::where('lesson_package_id', $packageId)
            ->where('id', $groupId)
            ->firstOrFail();
        
        // Check if group has members
        if ($group->registrations()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Kan groep niet verwijderen omdat er nog leden in zitten'
            ], 422);
        }
        
        $group->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Groep succesvol verwijderd'
        ]);
    }

    // Verbeterde removeFromGroup methode
    public function removeFromGroup(Request $request, $packageId)
    {
        $validated = $request->validate([
            'registration_id' => 'required|exists:lesson_registrations,id'
        ]);
        
        $registration = LessonRegistration::where('id', $validated['registration_id'])
            ->where('lesson_package_id', $packageId)
            ->firstOrFail();
        
        $registration->update(['assigned_group_id' => null]);
        
        return response()->json([
            'success' => true,
            'message' => 'Lid succesvol verwijderd uit groep'
        ]);
    }

    // Verbeterde assignToGroup methode met betere validatie
    public function assignToGroup(Request $request, $packageId, $groupId)
    {
        $validated = $request->validate([
            'registration_ids' => 'required|array',
            'registration_ids.*' => 'exists:lesson_registrations,id'
        ]);
        
        // Check if group belongs to package
        $group = LessonGroup::where('lesson_package_id', $packageId)
            ->where('id', $groupId)
            ->firstOrFail();
        
        // Check capacity
        $currentCount = $group->registrations()->count();
        $newCount = count($validated['registration_ids']);
        
        if (($currentCount + $newCount) > $group->max_participants) {
            $available = $group->max_participants - $currentCount;
            return response()->json([
                'success' => false,
                'message' => "Groep heeft maar {$available} plekken beschikbaar"
            ], 422);
        }
        
        // Assign registrations to group
        $updated = LessonRegistration::whereIn('id', $validated['registration_ids'])
            ->where('lesson_package_id', $packageId)
            ->whereNull('assigned_group_id') // Alleen leden die nog niet zijn toegewezen
            ->update(['assigned_group_id' => $groupId]);
        
        return response()->json([
            'success' => true,
            'message' => "{$updated} leden toegewezen aan groep"
        ]);
    }
    
    // Locations
    public function getLocations()
    {
        $locations = LessonLocation::where('is_active', true)->get();
        
        return response()->json([
            'success' => true,
            'data' => $locations
        ]);
    }
    
    public function storeLocation(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'type' => 'nullable|string',
            'capacity' => 'nullable|integer',
            'notes' => 'nullable|string',
        ]);
        
        $location = LessonLocation::create($validated);
        
        return response()->json([
            'success' => true,
            'data' => $location
        ]);
    }

    // ========================================
    // LESSON SCHEDULE MANAGEMENT
    // ========================================

    /**
     * Generate lesson schedule for a group
     */
    public function generateSchedule(Request $request, $packageId, $groupId)
    {
        $validated = $request->validate([
            // Optioneel: overschrijf pakket datums indien nodig
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'regenerate' => 'boolean', // Om te forceren als er al lessen zijn
        ]);
        
        $group = LessonGroup::where('lesson_package_id', $packageId)
            ->where('id', $groupId)
            ->firstOrFail();
        
        $package = LessonPackage::findOrFail($packageId);
        
        // Check if schedules already exist
        $existingCount = LessonSchedule::where('lesson_group_id', $groupId)->count();
        if ($existingCount > 0 && !$request->input('regenerate', false)) {
            return response()->json([
                'success' => false,
                'message' => "Er zijn al {$existingCount} lessen ingepland. Gebruik 'regenerate' om opnieuw te genereren."
            ], 422);
        }
        
        // Delete existing schedules if regenerating
        if ($request->input('regenerate', false)) {
            LessonSchedule::where('lesson_group_id', $groupId)->delete();
        }
        
        // Use package dates by default, or override if provided
        $startDate = Carbon::parse($validated['start_date'] ?? $package->start_date);
        $endDate = Carbon::parse($validated['end_date'] ?? $package->end_date);
        
        // Generate new schedules
        $schedules = [];
        $currentDate = $startDate->copy();
        $lessonsCreated = 0;
        $maxLessons = $package->total_lessons;
        
        // Als geen schedule_days zijn ingesteld, gebruik dan de available_days van het pakket
        $scheduleDays = !empty($group->schedule_days) ? $group->schedule_days : $package->available_days;
        
        while ($currentDate <= $endDate && $lessonsCreated < $maxLessons) {
            // Check if this day is in the schedule_days
            $dayName = strtolower($currentDate->format('l')); // monday, tuesday, etc.
            
            if (in_array($dayName, $scheduleDays ?? [])) {
                $schedules[] = [
                    'lesson_group_id' => $groupId,
                    'location_id' => $group->location_id,
                    'lesson_date' => $currentDate->format('Y-m-d'),
                    'start_time' => $group->default_start_time ?? '19:00',
                    'end_time' => $group->default_end_time ?? '20:00',
                    'status' => 'scheduled',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $lessonsCreated++;
            }
            
            $currentDate->addDay();
        }
        
        LessonSchedule::insert($schedules);
        
        return response()->json([
            'success' => true,
            'message' => "{$lessonsCreated} lessen ingepland",
            'data' => LessonSchedule::where('lesson_group_id', $groupId)->get()
        ]);
    }

    /**
     * Get schedule for a group
     */
    public function getGroupSchedule($packageId, $groupId)
    {
        $schedules = LessonSchedule::where('lesson_group_id', $groupId)
            ->with(['location'])
            ->orderBy('lesson_date')
            ->orderBy('start_time')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $schedules
        ]);
    }

    /**
     * Update a single lesson schedule
     */
    public function updateSchedule(Request $request, $packageId, $groupId, $scheduleId)
    {
        $validated = $request->validate([
            'lesson_date' => 'sometimes|date',
            'start_time' => 'sometimes',
            'end_time' => 'sometimes',
            'location_id' => 'sometimes|exists:lesson_locations,id',
            'status' => 'sometimes|in:scheduled,completed,cancelled',
            'notes' => 'nullable|string',
        ]);
        
        $schedule = LessonSchedule::where('lesson_group_id', $groupId)
            ->where('id', $scheduleId)
            ->firstOrFail();
        
        $schedule->update($validated);
        
        return response()->json([
            'success' => true,
            'data' => $schedule->load('location')
        ]);
    }

    /**
     * Cancel a lesson
     */
    public function cancelLesson(Request $request, $packageId, $groupId, $scheduleId)
    {
        $schedule = LessonSchedule::where('lesson_group_id', $groupId)
            ->where('id', $scheduleId)
            ->firstOrFail();
        
        $schedule->update([
            'status' => 'cancelled',
            'notes' => $request->input('reason', 'Les geannuleerd')
        ]);
        
        // TODO: Send notification to group members
        
        return response()->json([
            'success' => true,
            'message' => 'Les geannuleerd'
        ]);
    }

    /**
     * Get full calendar view of all lessons
     */
    public function getCalendar(Request $request, $packageId)
    {
        $package = LessonPackage::findOrFail($packageId);
        
        $schedules = LessonSchedule::whereHas('group', function($q) use ($packageId) {
                $q->where('lesson_package_id', $packageId);
            })
            ->with(['group', 'location'])
            ->whereBetween('lesson_date', [
                $request->input('start', now()->startOfMonth()),
                $request->input('end', now()->endOfMonth())
            ])
            ->get()
            ->map(function($schedule) {
                return [
                    'id' => $schedule->id,
                    'title' => $schedule->group->name,
                    'start' => $schedule->lesson_date . ' ' . $schedule->start_time,
                    'end' => $schedule->lesson_date . ' ' . $schedule->end_time,
                    'location' => $schedule->location?->name,
                    'status' => $schedule->status,
                    'color' => $this->getStatusColor($schedule->status),
                    'extendedProps' => [
                        'group_id' => $schedule->lesson_group_id,
                        'location' => $schedule->location?->name,
                        'trainer' => $schedule->group->trainer?->name,
                        'participants' => $schedule->group->registrations->count(),
                        'notes' => $schedule->notes
                    ]
                ];
            });
        
        return response()->json([
            'success' => true,
            'data' => $schedules
        ]);
    }

    private function getStatusColor($status)
    {
        return match($status) {
            'scheduled' => '#4CAF50',
            'completed' => '#9E9E9E', 
            'cancelled' => '#F44336',
            default => '#2196F3'
        };
    }

    // ========================================
    // TRAINER AVAILABILITY MANAGEMENT
    // ========================================

    /**
     * Set trainer availability for a package
     */
    public function setTrainerAvailability(Request $request, $packageId)
    {
        $validated = $request->validate([
            'trainer_id' => 'required|exists:users,id',
            'availabilities' => 'required|array',
            'availabilities.*.day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'availabilities.*.available_from' => 'required',
            'availabilities.*.available_until' => 'required',
            'availabilities.*.is_available' => 'boolean',
        ]);
        
        // Delete existing availability for this trainer and package
        TrainerAvailability::where('user_id', $validated['trainer_id'])
            ->where('lesson_package_id', $packageId)
            ->delete();
        
        // Insert new availability
        foreach ($validated['availabilities'] as $availability) {
            TrainerAvailability::create([
                'user_id' => $validated['trainer_id'],
                'lesson_package_id' => $packageId,
                'day_of_week' => $availability['day_of_week'],
                'available_from' => $availability['available_from'],
                'available_until' => $availability['available_until'],
                'is_available' => $availability['is_available'] ?? true,
            ]);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Beschikbaarheid opgeslagen'
        ]);
    }

    /**
     * Get trainer availability
     */
    public function getTrainerAvailability($packageId, $trainerId)
    {
        $availability = TrainerAvailability::where('user_id', $trainerId)
            ->where('lesson_package_id', $packageId)
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $availability
        ]);
    }

    /**
     * Get all available trainers for a specific time slot
     */
    public function getAvailableTrainers(Request $request, $packageId)
    {
        $validated = $request->validate([
            'day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'time' => 'required', // format: HH:MM
        ]);
        
        $availableTrainers = TrainerAvailability::where('lesson_package_id', $packageId)
            ->where('day_of_week', $validated['day_of_week'])
            ->where('available_from', '<=', $validated['time'])
            ->where('available_until', '>=', $validated['time'])
            ->where('is_available', true)
            ->with('trainer')
            ->get()
            ->pluck('trainer');
        
        return response()->json([
            'success' => true,
            'data' => $availableTrainers
        ]);
    }

    // ========================================
    // ATTENDANCE MANAGEMENT
    // ========================================

    /**
     * Get attendance for a specific lesson
     */
    public function getLessonAttendance($packageId, $groupId, $scheduleId)
    {
        $schedule = LessonSchedule::where('lesson_group_id', $groupId)
            ->where('id', $scheduleId)
            ->with(['group.registrations.user'])
            ->firstOrFail();
        
        // Get all registered users for this group
        $registeredUsers = $schedule->group->registrations->map(function($registration) use ($scheduleId) {
            $attendance = LessonAttendance::where('lesson_schedule_id', $scheduleId)
                ->where('user_id', $registration->user_id)
                ->first();
            
            return [
                'user_id' => $registration->user_id,
                'user_name' => $registration->user->name,
                'user_email' => $registration->user->email,
                'status' => $attendance ? $attendance->status : null,
                'notes' => $attendance ? $attendance->notes : null,
                'checked_at' => $attendance ? $attendance->checked_at : null,
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => [
                'lesson' => $schedule,
                'attendances' => $registeredUsers
            ]
        ]);
    }

    /**
     * Update attendance for a lesson
     */
    public function updateAttendance(Request $request, $packageId, $groupId, $scheduleId)
    {
        $validated = $request->validate([
            'attendances' => 'required|array',
            'attendances.*.user_id' => 'required|exists:users,id',
            'attendances.*.status' => 'required|in:present,absent,excused,late',
            'attendances.*.notes' => 'nullable|string',
        ]);
        
        $schedule = LessonSchedule::where('lesson_group_id', $groupId)
            ->where('id', $scheduleId)
            ->firstOrFail();
        
        foreach ($validated['attendances'] as $attendance) {
            LessonAttendance::updateOrCreate(
                [
                    'lesson_schedule_id' => $scheduleId,
                    'user_id' => $attendance['user_id'],
                ],
                [
                    'status' => $attendance['status'],
                    'notes' => $attendance['notes'] ?? null,
                    'checked_at' => now(),
                    'checked_by' => auth()->id(),
                ]
            );
        }
        
        // Mark lesson as completed if not already
        if ($schedule->status === 'scheduled') {
            $schedule->update(['status' => 'completed']);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Aanwezigheid bijgewerkt'
        ]);
    }

    /**
     * Quick mark attendance (single user)
     */
    public function markAttendance(Request $request, $packageId, $groupId, $scheduleId)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'status' => 'required|in:present,absent,excused,late',
            'notes' => 'nullable|string',
        ]);
        
        $schedule = LessonSchedule::where('lesson_group_id', $groupId)
            ->where('id', $scheduleId)
            ->firstOrFail();
        
        $attendance = LessonAttendance::updateOrCreate(
            [
                'lesson_schedule_id' => $scheduleId,
                'user_id' => $validated['user_id'],
            ],
            [
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
                'checked_at' => now(),
                'checked_by' => auth()->id(),
            ]
        );
        
        return response()->json([
            'success' => true,
            'data' => $attendance
        ]);
    }

    /**
     * Get attendance statistics for a user
     */
    public function getUserAttendanceStats($packageId, $userId)
    {
        $package = LessonPackage::findOrFail($packageId);
        
        // Get all lessons for groups where this user is registered
        $groupIds = LessonRegistration::where('lesson_package_id', $packageId)
            ->where('user_id', $userId)
            ->pluck('assigned_group_id')
            ->filter();
        
        if ($groupIds->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'total_lessons' => 0,
                    'present' => 0,
                    'absent' => 0,
                    'excused' => 0,
                    'late' => 0,
                    'attendance_rate' => 0,
                ]
            ]);
        }
        
        $totalLessons = LessonSchedule::whereIn('lesson_group_id', $groupIds)
            ->where('status', '!=', 'cancelled')
            ->count();
        
        $attendanceStats = LessonAttendance::where('user_id', $userId)
            ->whereHas('schedule', function($q) use ($groupIds) {
                $q->whereIn('lesson_group_id', $groupIds);
            })
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        
        $present = $attendanceStats['present'] ?? 0;
        $absent = $attendanceStats['absent'] ?? 0;
        $excused = $attendanceStats['excused'] ?? 0;
        $late = $attendanceStats['late'] ?? 0;
        
        $attendedLessons = $present + $late;
        $completedLessons = $present + $absent + $excused + $late;
        $attendanceRate = $completedLessons > 0 ? round(($attendedLessons / $completedLessons) * 100, 1) : 0;
        
        return response()->json([
            'success' => true,
            'data' => [
                'total_lessons' => $totalLessons,
                'completed_lessons' => $completedLessons,
                'present' => $present,
                'absent' => $absent,
                'excused' => $excused,
                'late' => $late,
                'attendance_rate' => $attendanceRate,
            ]
        ]);
    }

    /**
     * Get attendance statistics for a group
     */
    public function getGroupAttendanceStats($packageId, $groupId)
    {
        $group = LessonGroup::where('lesson_package_id', $packageId)
            ->where('id', $groupId)
            ->with(['registrations.user'])
            ->firstOrFail();
        
        $schedules = LessonSchedule::where('lesson_group_id', $groupId)
            ->where('status', 'completed')
            ->pluck('id');
        
        $stats = [];
        
        foreach ($group->registrations as $registration) {
            $attendances = LessonAttendance::where('user_id', $registration->user_id)
                ->whereIn('lesson_schedule_id', $schedules)
                ->get();
            
            $present = $attendances->where('status', 'present')->count();
            $absent = $attendances->where('status', 'absent')->count();
            $excused = $attendances->where('status', 'excused')->count();
            $late = $attendances->where('status', 'late')->count();
            
            $total = $schedules->count();
            $attendanceRate = $total > 0 ? round((($present + $late) / $total) * 100, 1) : 0;
            
            $stats[] = [
                'user_id' => $registration->user_id,
                'user_name' => $registration->user->name,
                'total_lessons' => $total,
                'present' => $present,
                'absent' => $absent,
                'excused' => $excused,
                'late' => $late,
                'attendance_rate' => $attendanceRate,
            ];
        }
        
        // Sort by attendance rate
        usort($stats, function($a, $b) {
            return $b['attendance_rate'] <=> $a['attendance_rate'];
        });
        
        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get overall package attendance statistics
     */
    public function getPackageAttendanceStats($packageId)
    {
        $package = LessonPackage::findOrFail($packageId);
        
        $totalLessons = LessonSchedule::whereHas('group', function($q) use ($packageId) {
                $q->where('lesson_package_id', $packageId);
            })
            ->where('status', 'completed')
            ->count();
        
        $totalPossibleAttendances = LessonRegistration::where('lesson_package_id', $packageId)
            ->whereNotNull('assigned_group_id')
            ->count() * $totalLessons;
        
        if ($totalPossibleAttendances === 0) {
            return response()->json([
                'success' => true,
                'data' => [
                    'average_attendance_rate' => 0,
                    'total_lessons_given' => 0,
                    'total_attendances' => 0,
                    'by_status' => [],
                ]
            ]);
        }
        
        $attendancesByStatus = LessonAttendance::whereHas('schedule.group', function($q) use ($packageId) {
                $q->where('lesson_package_id', $packageId);
            })
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        
        $totalPresent = ($attendancesByStatus['present'] ?? 0) + ($attendancesByStatus['late'] ?? 0);
        $totalRecorded = array_sum($attendancesByStatus);
        $averageAttendanceRate = $totalRecorded > 0 ? round(($totalPresent / $totalRecorded) * 100, 1) : 0;
        
        return response()->json([
            'success' => true,
            'data' => [
                'average_attendance_rate' => $averageAttendanceRate,
                'total_lessons_given' => $totalLessons,
                'total_attendances' => $totalRecorded,
                'total_possible_attendances' => $totalPossibleAttendances,
                'by_status' => $attendancesByStatus,
            ]
        ]);
    }

    // ========================================
    // NOTIFICATION MANAGEMENT
    // ========================================

    /**
     * Send notification to group members
     */
    public function sendNotification(Request $request, $packageId)
    {
        $validated = $request->validate([
            'group_id' => 'required|exists:lesson_groups,id',
            'type' => 'required|in:custom,lesson_reminder,lesson_cancelled,schedule_change',
            'subject' => 'required_if:type,custom|nullable|string',
            'message' => 'required_if:type,custom|nullable|string',
            'send_email' => 'boolean',
            'send_sms' => 'boolean',
            'test_mode' => 'boolean',
            'lesson_schedule_id' => 'required_if:type,lesson_reminder|nullable|exists:lesson_schedules,id',
            'hours_before' => 'nullable|integer|min:1|max:72',
            'scheduled_at' => 'nullable|date|after:now',
        ]);
        
        $group = LessonGroup::where('lesson_package_id', $packageId)
            ->where('id', $validated['group_id'])
            ->with('registrations.user')
            ->firstOrFail();
        
        // Get recipients
        $recipients = [];
        if ($validated['test_mode'] ?? false) {
            // In test mode, only send to current user
            $recipients = [auth()->user()];
        } else {
            // Send to all group members
            $recipients = $group->registrations->map(function($reg) {
                return $reg->user;
            });
        }
        
        // Prepare message based on type
        $subject = $validated['subject'] ?? '';
        $message = $validated['message'] ?? '';
        
        if ($validated['type'] === 'lesson_reminder' && isset($validated['lesson_schedule_id'])) {
            $lesson = LessonSchedule::with('location')->find($validated['lesson_schedule_id']);
            $subject = 'Herinnering: Tennis les ' . date('d/m', strtotime($lesson->lesson_date));
            $message = $this->prepareLessonReminderMessage($lesson, $group);
        } elseif ($validated['type'] === 'lesson_cancelled') {
            $subject = $subject ?: 'Les geannuleerd';
            $message = $message ?: 'Uw les is geannuleerd. U ontvangt binnenkort meer informatie.';
        } elseif ($validated['type'] === 'schedule_change') {
            $subject = $subject ?: 'Rooster wijziging';
            $message = $message ?: 'Er is een wijziging in het lesrooster. Bekijk de website voor meer informatie.';
        }
        
        // Send notifications (for now just log, implement actual sending later)
        $sentCount = 0;
        foreach ($recipients as $recipient) {
            // Replace placeholders in message
            $personalizedMessage = str_replace(
                ['{name}', '{group}', '{date}', '{time}', '{location}'],
                [
                    $recipient->name,
                    $group->name,
                    isset($lesson) ? date('d/m/Y', strtotime($lesson->lesson_date)) : '',
                    isset($lesson) ? $lesson->start_time : '',
                    isset($lesson) && $lesson->location ? $lesson->location->name : 'TC Zutendaal',
                ],
                $message
            );
            
            if ($validated['send_email'] ?? true) {
                // TODO: Actually send email here
                // For now, just log
                \Log::info('Would send email to: ' . $recipient->email, [
                    'subject' => $subject,
                    'message' => $personalizedMessage,
                ]);
                $sentCount++;
            }
            
            if ($validated['send_sms'] ?? false) {
                // TODO: Implement SMS sending
                \Log::info('Would send SMS to: ' . $recipient->mobile);
            }
        }
        
        // Store notification in database (optional, for tracking)
        // You might want to create a notifications table for this
        
        return response()->json([
            'success' => true,
            'message' => ($validated['test_mode'] ?? false) 
                ? 'Test notificatie verstuurd naar jouw email' 
                : "Notificatie verstuurd naar {$sentCount} leden",
            'recipients_count' => $sentCount,
        ]);
    }
    
    /**
     * Get notifications history
     */
    public function getNotifications($packageId)
    {
        // TODO: Implement if you create a notifications table
        return response()->json([
            'success' => true,
            'data' => [],
            'message' => 'Notificatie geschiedenis komt binnenkort',
        ]);
    }
    
    /**
     * Cancel scheduled notification
     */
    public function cancelNotification($packageId, $notificationId)
    {
        // TODO: Implement if you create scheduled notifications
        return response()->json([
            'success' => true,
            'message' => 'Geplande notificatie geannuleerd',
        ]);
    }
    
    /**
     * Prepare lesson reminder message
     */
    private function prepareLessonReminderMessage($lesson, $group)
    {
        $date = date('d/m/Y', strtotime($lesson->lesson_date));
        $time = substr($lesson->start_time, 0, 5);
        $location = $lesson->location ? $lesson->location->name : 'TC Zutendaal';
        
        return "Beste {name},

        Dit is een herinnering voor je tennisles morgen.

        Groep: {$group->name}
        Datum: {$date}
        Tijd: {$time}
        Locatie: {$location}

        Tot morgen!

        Met sportieve groeten,
        TC Zutendaal";
    }

    /**
     * Get financial report for a package
     */
    public function getFinancialReport($packageId)
    {
        $package = LessonPackage::with([
            'registrations.user',
            'registrations.assignedGroup',
            'groups.trainer',
            'groups.registrations'
        ])->findOrFail($packageId);
        
        // Calculate financial metrics
        $registrations = $package->registrations;
        
        // Add calculated amount field to each registration
        $registrations = $registrations->map(function($reg) use ($package) {
            $amount = $reg->user->membership_type === 'non_member' 
                ? $package->price_non_members 
                : $package->price_members;
            
            $reg->amount = $amount;
            $reg->assignedGroup = $reg->assignedGroup;
            
            return $reg;
        });
        
        // Calculate summary statistics
        $stats = [
            'total_registrations' => $registrations->count(),
            'expected_revenue' => $registrations->sum('amount'),
            'actual_revenue' => $registrations->where('payment_status', 'paid')->sum('amount_paid'),
            'outstanding' => 0,
            'unpaid_count' => $registrations->where('payment_status', 'unpaid')->count(),
            'average_per_participant' => $registrations->count() > 0 
                ? $registrations->sum('amount') / $registrations->count() 
                : 0,
        ];
        
        $stats['outstanding'] = $stats['expected_revenue'] - $stats['actual_revenue'];
        $stats['collection_rate'] = $stats['expected_revenue'] > 0 
            ? round(($stats['actual_revenue'] / $stats['expected_revenue']) * 100, 1) 
            : 0;
        
        // Payment status breakdown
        $paymentStatusBreakdown = $registrations->groupBy('payment_status')->map(function($group) {
            return [
                'count' => $group->count(),
                'amount' => $group->sum('amount'),
                'amount_paid' => $group->sum('amount_paid'),
            ];
        });
        
        // Member type breakdown
        $memberTypeBreakdown = $registrations->groupBy('user.membership_type')->map(function($group) use ($package) {
            $type = $group->first()->user->membership_type;
            $rate = $type === 'non_member' 
                ? $package->price_non_members 
                : $package->price_members;
            
            return [
                'count' => $group->count(),
                'rate' => $rate,
                'total' => $group->sum('amount'),
            ];
        });
        
        // Group financial data
        $groupFinancials = $package->groups->map(function($group) use ($package) {
            $groupRegistrations = $group->registrations;
            
            $expectedGroupRevenue = $groupRegistrations->sum(function($reg) use ($package) {
                return $reg->user->membership_type === 'non_member' 
                    ? $package->price_non_members 
                    : $package->price_members;
            });
            
            $collectedGroupRevenue = $groupRegistrations
                ->where('payment_status', 'paid')
                ->sum('amount_paid');
            
            return [
                'id' => $group->id,
                'name' => $group->name,
                'trainer' => $group->trainer,
                'participants' => $groupRegistrations->count(),
                'max_participants' => $group->max_participants,
                'fill_rate' => $group->max_participants > 0 
                    ? round(($groupRegistrations->count() / $group->max_participants) * 100, 1) 
                    : 0,
                'expected_revenue' => $expectedGroupRevenue,
                'collected_revenue' => $collectedGroupRevenue,
                'outstanding' => $expectedGroupRevenue - $collectedGroupRevenue,
                'collection_rate' => $expectedGroupRevenue > 0 
                    ? round(($collectedGroupRevenue / $expectedGroupRevenue) * 100, 1) 
                    : 0,
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => [
                'package' => $package,
                'registrations' => $registrations,
                'stats' => $stats,
                'payment_status_breakdown' => $paymentStatusBreakdown,
                'member_type_breakdown' => $memberTypeBreakdown,
                'group_financials' => $groupFinancials,
            ]
        ]);
    }

    /**
     * Mark registration as paid
     */
    public function markRegistrationAsPaid(Request $request, $registrationId)
    {
        $validated = $request->validate([
            'amount_paid' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);
        
        $registration = LessonRegistration::findOrFail($registrationId);
        
        // Get the package to determine the amount
        $package = $registration->package;
        $amount = $registration->user->membership_type === 'non_member' 
            ? $package->price_non_members 
            : $package->price_members;
        
        $registration->update([
            'payment_status' => 'paid',
            'amount_paid' => $validated['amount_paid'] ?? $amount,
            'paid_at' => now(),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Betaling geregistreerd',
            'data' => $registration
        ]);
    }

    /**
     * Send payment reminders
     */
    public function sendPaymentReminders(Request $request, $packageId)
    {
        $package = LessonPackage::findOrFail($packageId);
        
        $unpaidRegistrations = LessonRegistration::where('lesson_package_id', $packageId)
            ->where('payment_status', 'unpaid')
            ->with('user')
            ->get();
        
        $sentCount = 0;
        foreach ($unpaidRegistrations as $registration) {
            try {
                // Send email reminder
                Mail::to($registration->user->email)->queue(
                    new \App\Mail\PaymentReminder($registration, $package)
                );
                $sentCount++;
                
                // Log the reminder
                \Log::info('Payment reminder sent', [
                    'user_id' => $registration->user_id,
                    'package_id' => $packageId,
                    'registration_id' => $registration->id,
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to send payment reminder', [
                    'error' => $e->getMessage(),
                    'registration_id' => $registration->id,
                ]);
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => "{$sentCount} herinneringen verstuurd",
            'sent_count' => $sentCount,
        ]);
    }

    /**
     * Send individual payment reminder
     */
    public function sendIndividualPaymentReminder($registrationId)
    {
        $registration = LessonRegistration::with(['user', 'package'])->findOrFail($registrationId);
        
        if ($registration->payment_status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Deze inschrijving is al betaald'
            ], 422);
        }
        
        try {
            Mail::to($registration->user->email)->queue(
                new \App\Mail\PaymentReminder($registration, $registration->package)
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Herinnering verstuurd naar ' . $registration->user->name,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send payment reminder', [
                'error' => $e->getMessage(),
                'registration_id' => $registration->id,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Fout bij versturen herinnering',
            ], 500);
        }
    }

    /**
     * Export financial report as PDF
     */
    public function exportFinancialReport($packageId)
    {
        $package = LessonPackage::with([
            'registrations.user',
            'registrations.assignedGroup',
            'groups.trainer',
        ])->findOrFail($packageId);
        
        // Generate PDF (you'll need to install a PDF package like barryvdh/laravel-dompdf)
        // For now, return CSV
        
        $registrations = $package->registrations;
        
        $csvData = [];
        $csvData[] = ['Financieel Overzicht - ' . $package->name];
        $csvData[] = ['Gegenereerd op', now()->format('d-m-Y H:i')];
        $csvData[] = [];
        $csvData[] = ['Naam', 'Email', 'Type', 'Groep', 'Bedrag', 'Status', 'Betaald'];
        
        foreach ($registrations as $reg) {
            $amount = $reg->user->membership_type === 'non_member' 
                ? $package->price_non_members 
                : $package->price_members;
            
            $csvData[] = [
                $reg->user->name,
                $reg->user->email,
                $reg->user->membership_type,
                $reg->assignedGroup?->name ?? 'Niet toegewezen',
                $amount,
                $reg->payment_status,
                $reg->amount_paid
            ];
        }
        
        // Add summary
        $csvData[] = [];
        $csvData[] = ['Samenvatting'];
        $csvData[] = ['Totaal inschrijvingen', $registrations->count()];
        $csvData[] = ['Totaal verwacht', $registrations->sum(function($reg) use ($package) {
            return $reg->user->membership_type === 'non_member' 
                ? $package->price_non_members 
                : $package->price_members;
        })];
        $csvData[] = ['Totaal ontvangen', $registrations->where('payment_status', 'paid')->sum('amount_paid')];
        $csvData[] = ['Openstaand', $registrations->sum(function($reg) use ($package) {
            if ($reg->payment_status === 'paid') return 0;
            return $reg->user->membership_type === 'non_member' 
                ? $package->price_non_members 
                : $package->price_members;
        })];
        
        $filename = 'financieel-overzicht-' . Str::slug($package->name) . '-' . now()->format('Y-m-d') . '.csv';
        
        return response()->streamDownload(function() use ($csvData) {
            $file = fopen('php://output', 'w');
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        }, $filename);
    }
}
