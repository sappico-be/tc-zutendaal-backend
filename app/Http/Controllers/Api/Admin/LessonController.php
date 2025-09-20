<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\LessonPackage;
use App\Models\LessonRegistration;
use App\Models\LessonGroup;
use App\Models\LessonLocation;
use Illuminate\Http\Request;

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

    // Assign registrations to group
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
            return response()->json([
                'success' => false,
                'message' => 'Groep heeft niet genoeg plaats'
            ], 422);
        }
        
        // Assign registrations to group
        LessonRegistration::whereIn('id', $validated['registration_ids'])
            ->where('lesson_package_id', $packageId)
            ->update(['assigned_group_id' => $groupId]);
        
        return response()->json([
            'success' => true,
            'message' => 'Leden toegewezen aan groep'
        ]);
    }

    // Remove from group
    public function removeFromGroup(Request $request, $packageId)
    {
        $validated = $request->validate([
            'registration_id' => 'required|exists:lesson_registrations,id'
        ]);
        
        LessonRegistration::where('id', $validated['registration_id'])
            ->where('lesson_package_id', $packageId)
            ->update(['assigned_group_id' => null]);
        
        return response()->json([
            'success' => true,
            'message' => 'Lid verwijderd uit groep'
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
}
