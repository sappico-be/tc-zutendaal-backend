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
}
