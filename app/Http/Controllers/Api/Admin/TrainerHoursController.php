<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainerHourRegistration;
use App\Models\TrainerContract;
use App\Models\TrainerHourSummary;
use App\Models\User;
use App\Models\LessonSchedule;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TrainerHoursController extends Controller
{
    /**
     * Get hour registrations
     */
    public function index(Request $request)
    {
        $query = TrainerHourRegistration::with(['trainer', 'lessonSchedule', 'approvedBy']);
        
        // Filter by trainer
        if ($request->has('trainer_id')) {
            $query->where('user_id', $request->trainer_id);
        }
        
        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        
        // Date range filter
        if ($request->has('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }
        
        // Month filter (for monthly overview)
        if ($request->has('month') && $request->has('year')) {
            $query->whereYear('date', $request->year)
                  ->whereMonth('date', $request->month);
        }
        
        // If user is trainer, only show their own hours
        $user = auth()->user();
        if ($user->role === 'trainer' && !$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }
        
        $registrations = $query->orderBy('date', 'desc')
                               ->orderBy('start_time', 'desc')
                               ->paginate($request->get('per_page', 20));
        
        return response()->json([
            'success' => true,
            'data' => $registrations,
        ]);
    }

    /**
     * Store new hour registration
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'sometimes|exists:users,id',
            'lesson_schedule_id' => 'nullable|exists:lesson_schedules,id',
            'date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'type' => 'required|in:lesson,preparation,meeting,tournament,other',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);
        
        // Use authenticated user if not admin
        $user = auth()->user();
        if (!$user->isAdmin() && $user->role === 'trainer') {
            $validated['user_id'] = $user->id;
        } elseif (!isset($validated['user_id'])) {
            $validated['user_id'] = $user->id;
        }
        
        // Get trainer's current contract
        $contract = TrainerContract::where('user_id', $validated['user_id'])
                                   ->current()
                                   ->first();
        
        if ($contract) {
            $validated['hourly_rate'] = $contract->getRateForType($validated['type']);
        } else {
            // Use default rate from user or system default
            $trainer = User::find($validated['user_id']);
            $validated['hourly_rate'] = $trainer->default_hourly_rate ?? 25.00;
        }
        
        // Calculate hours
        $start = Carbon::parse($validated['date'] . ' ' . $validated['start_time']);
        $end = Carbon::parse($validated['date'] . ' ' . $validated['end_time']);
        
        if ($end < $start) {
            $end->addDay();
        }
        
        $validated['hours'] = round($start->diffInMinutes($end) / 60, 2);
        $validated['total_amount'] = $validated['hours'] * $validated['hourly_rate'];
        
        // Auto-approve if admin
        if ($user->isAdmin()) {
            $validated['status'] = 'approved';
            $validated['approved_by'] = $user->id;
            $validated['approved_at'] = now();
        } else {
            $validated['status'] = 'pending';
        }
        
        $registration = TrainerHourRegistration::create($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Uren succesvol geregistreerd',
            'data' => $registration->load('trainer'),
        ]);
    }

    /**
     * Update hour registration
     */
    public function update(Request $request, $id)
    {
        $registration = TrainerHourRegistration::findOrFail($id);
        
        // Check permission
        $user = auth()->user();
        if (!$user->isAdmin() && $registration->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Geen toestemming om deze registratie te wijzigen',
            ], 403);
        }
        
        // Can't edit approved registrations unless admin
        if ($registration->status === 'approved' && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Goedgekeurde registraties kunnen niet meer worden gewijzigd',
            ], 422);
        }
        
        $validated = $request->validate([
            'date' => 'sometimes|date',
            'start_time' => 'sometimes',
            'end_time' => 'sometimes',
            'type' => 'sometimes|in:lesson,preparation,meeting,tournament,other',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'hourly_rate' => 'sometimes|numeric|min:0',
        ]);
        
        // Recalculate if times change
        if (isset($validated['start_time']) || isset($validated['end_time']) || isset($validated['date'])) {
            $date = $validated['date'] ?? $registration->date;
            $start_time = $validated['start_time'] ?? $registration->start_time;
            $end_time = $validated['end_time'] ?? $registration->end_time;
            
            $start = Carbon::parse($date . ' ' . $start_time);
            $end = Carbon::parse($date . ' ' . $end_time);
            
            if ($end < $start) {
                $end->addDay();
            }
            
            $validated['hours'] = round($start->diffInMinutes($end) / 60, 2);
        }
        
        // Recalculate total
        $hours = $validated['hours'] ?? $registration->hours;
        $rate = $validated['hourly_rate'] ?? $registration->hourly_rate;
        $validated['total_amount'] = $hours * $rate;
        
        // Reset approval if edited
        if (!$user->isAdmin() && $registration->status === 'rejected') {
            $validated['status'] = 'pending';
            $validated['approved_by'] = null;
            $validated['approved_at'] = null;
            $validated['admin_notes'] = null;
        }
        
        $registration->update($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Registratie bijgewerkt',
            'data' => $registration->fresh()->load('trainer'),
        ]);
    }

    /**
     * Delete hour registration
     */
    public function destroy($id)
    {
        $registration = TrainerHourRegistration::findOrFail($id);
        
        // Check permission
        $user = auth()->user();
        if (!$user->isAdmin() && $registration->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Geen toestemming om deze registratie te verwijderen',
            ], 403);
        }
        
        // Can't delete approved registrations unless admin
        if ($registration->status === 'approved' && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Goedgekeurde registraties kunnen niet worden verwijderd',
            ], 422);
        }
        
        $registration->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Registratie verwijderd',
        ]);
    }

    /**
     * Approve hour registration (admin only)
     */
    public function approve(Request $request, $id)
    {
        $registration = TrainerHourRegistration::findOrFail($id);
        
        $registration->approve(auth()->id());
        
        return response()->json([
            'success' => true,
            'message' => 'Uren goedgekeurd',
            'data' => $registration->fresh()->load('approvedBy'),
        ]);
    }

    /**
     * Reject hour registration (admin only)
     */
    public function reject(Request $request, $id)
    {
        $registration = TrainerHourRegistration::findOrFail($id);
        
        $validated = $request->validate([
            'reason' => 'required|string',
        ]);
        
        $registration->reject(auth()->id(), $validated['reason']);
        
        return response()->json([
            'success' => true,
            'message' => 'Uren afgewezen',
            'data' => $registration->fresh()->load('approvedBy'),
        ]);
    }

    /**
     * Bulk approve registrations
     */
    public function bulkApprove(Request $request)
    {
        $validated = $request->validate([
            'registration_ids' => 'required|array',
            'registration_ids.*' => 'exists:trainer_hour_registrations,id',
        ]);
        
        $registrations = TrainerHourRegistration::whereIn('id', $validated['registration_ids'])
                                                 ->where('status', 'pending')
                                                 ->get();
        
        foreach ($registrations as $registration) {
            $registration->approve(auth()->id());
        }
        
        return response()->json([
            'success' => true,
            'message' => $registrations->count() . ' registraties goedgekeurd',
        ]);
    }

    /**
     * Import hours from lesson schedules
     */
    public function importFromSchedule(Request $request)
    {
        $validated = $request->validate([
            'trainer_id' => 'required|exists:users,id',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);
        
        // Find all completed lessons for this trainer in date range
        $lessons = LessonSchedule::whereHas('group', function($q) use ($validated) {
                $q->where('trainer_id', $validated['trainer_id']);
            })
            ->where('status', 'completed')
            ->whereBetween('lesson_date', [$validated['date_from'], $validated['date_to']])
            ->whereNotIn('id', function($query) use ($validated) {
                $query->select('lesson_schedule_id')
                      ->from('trainer_hour_registrations')
                      ->where('user_id', $validated['trainer_id'])
                      ->whereNotNull('lesson_schedule_id');
            })
            ->get();
        
        $imported = 0;
        $contract = TrainerContract::where('user_id', $validated['trainer_id'])
                                   ->current()
                                   ->first();
        
        $trainer = User::find($validated['trainer_id']);
        $defaultRate = $contract ? $contract->hourly_rate : ($trainer->default_hourly_rate ?? 25.00);
        
        foreach ($lessons as $lesson) {
            TrainerHourRegistration::create([
                'user_id' => $validated['trainer_id'],
                'lesson_schedule_id' => $lesson->id,
                'date' => $lesson->lesson_date,
                'start_time' => $lesson->start_time,
                'end_time' => $lesson->end_time,
                'type' => 'lesson',
                'description' => 'Les: ' . $lesson->group->name,
                'hourly_rate' => $defaultRate,
                'status' => 'pending',
            ]);
            $imported++;
        }
        
        return response()->json([
            'success' => true,
            'message' => "{$imported} lessen geïmporteerd",
            'imported_count' => $imported,
        ]);
    }

    /**
     * Get monthly summary
     */
    public function getMonthlySummary(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2030',
            'month' => 'required|integer|min:1|max:12',
            'trainer_id' => 'sometimes|exists:users,id',
        ]);
        
        $user = auth()->user();
        $trainerId = $validated['trainer_id'] ?? null;
        
        // If not admin, can only see own summary
        if (!$user->isAdmin() && $user->role === 'trainer') {
            $trainerId = $user->id;
        }
        
        // Get or create summary
        $summary = TrainerHourSummary::firstOrCreate(
            [
                'user_id' => $trainerId,
                'year' => $validated['year'],
                'month' => $validated['month'],
            ],
            [
                'total_hours' => 0,
                'total_amount' => 0,
                'status' => 'draft',
            ]
        );
        
        // Calculate totals
        $summary->calculateTotals();
        
        // Get detailed registrations
        $registrations = TrainerHourRegistration::where('user_id', $trainerId)
            ->whereYear('date', $validated['year'])
            ->whereMonth('date', $validated['month'])
            ->with(['lessonSchedule'])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $summary->load('trainer'),
                'registrations' => $registrations,
                'statistics' => [
                    'by_type' => $registrations->groupBy('type')->map->count(),
                    'by_status' => $registrations->groupBy('status')->map->count(),
                    'total_pending' => $registrations->where('status', 'pending')->sum('total_amount'),
                    'total_approved' => $registrations->where('status', 'approved')->sum('total_amount'),
                ],
            ],
        ]);
    }

    /**
     * Submit monthly summary for approval
     */
    public function submitMonthly(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
            'notes' => 'nullable|string',
        ]);
        
        $user = auth()->user();
        
        $summary = TrainerHourSummary::where('user_id', $user->id)
            ->where('year', $validated['year'])
            ->where('month', $validated['month'])
            ->firstOrFail();
        
        if ($summary->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Deze periode is al ingediend',
            ], 422);
        }
        
        // Check if all hours are approved
        $pendingCount = TrainerHourRegistration::where('user_id', $user->id)
            ->whereYear('date', $validated['year'])
            ->whereMonth('date', $validated['month'])
            ->where('status', 'pending')
            ->count();
        
        if ($pendingCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Er zijn nog {$pendingCount} uren niet goedgekeurd",
            ], 422);
        }
        
        $summary->submit();
        $summary->notes = $validated['notes'] ?? null;
        $summary->save();
        
        // Send notification to admin
        // TODO: Implement notification
        
        return response()->json([
            'success' => true,
            'message' => 'Maandoverzicht ingediend voor goedkeuring',
            'data' => $summary,
        ]);
    }

    /**
     * Get trainer contracts
     */
    public function getContracts(Request $request)
    {
        $query = TrainerContract::with('trainer');
        
        if ($request->has('trainer_id')) {
            $query->where('user_id', $request->trainer_id);
        }
        
        if ($request->has('active')) {
            $query->active();
        }
        
        $contracts = $query->orderBy('start_date', 'desc')->get();
        
        return response()->json([
            'success' => true,
            'data' => $contracts,
        ]);
    }

    /**
     * Store trainer contract
     */
    public function storeContract(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'hourly_rate' => 'required|numeric|min:0',
            'preparation_rate' => 'nullable|numeric|min:0',
            'tournament_rate' => 'nullable|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'contract_type' => 'required|in:freelance,employee,volunteer',
            'max_hours_per_week' => 'nullable|integer|min:1',
            'max_hours_per_month' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);
        
        // Deactivate other contracts for this trainer if this is active
        if ($validated['is_active'] ?? false) {
            TrainerContract::where('user_id', $validated['user_id'])
                          ->update(['is_active' => false]);
        }
        
        $contract = TrainerContract::create($validated);
        
        // Update user to track hours
        User::where('id', $validated['user_id'])->update([
            'tracks_hours' => true,
            'default_hourly_rate' => $validated['hourly_rate'],
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Contract aangemaakt',
            'data' => $contract->load('trainer'),
        ]);
    }

    /**
     * Get payroll overview
     */
    public function getPayrollOverview(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
        ]);
        
        $summaries = TrainerHourSummary::with(['trainer', 'approvedByUser'])
            ->where('year', $validated['year'])
            ->where('month', $validated['month'])
            ->get();
        
        $totalAmount = $summaries->sum('total_amount');
        $totalHours = $summaries->sum('total_hours');
        $unpaidAmount = $summaries->where('status', 'approved')
                                  ->whereNull('paid_at')
                                  ->sum('total_amount');
        
        return response()->json([
            'success' => true,
            'data' => [
                'summaries' => $summaries,
                'statistics' => [
                    'total_trainers' => $summaries->count(),
                    'total_hours' => $totalHours,
                    'total_amount' => $totalAmount,
                    'unpaid_amount' => $unpaidAmount,
                    'by_status' => $summaries->groupBy('status')->map->count(),
                ],
            ],
        ]);
    }

    /**
     * Mark summary as paid
     */
    public function markAsPaid(Request $request, $summaryId)
    {
        $summary = TrainerHourSummary::findOrFail($summaryId);
        
        if ($summary->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Alleen goedgekeurde overzichten kunnen als betaald worden gemarkeerd',
            ], 422);
        }
        
        $validated = $request->validate([
            'payment_reference' => 'nullable|string',
        ]);
        
        $summary->markAsPaid($validated['payment_reference'] ?? null);
        
        return response()->json([
            'success' => true,
            'message' => 'Overzicht gemarkeerd als betaald',
            'data' => $summary,
        ]);
    }

    /**
     * Export hours to CSV
     */
    public function exportHours(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
            'trainer_id' => 'nullable|exists:users,id',
        ]);
        
        $query = TrainerHourRegistration::with(['trainer', 'lessonSchedule.group'])
            ->whereYear('date', $validated['year'])
            ->whereMonth('date', $validated['month']);
        
        if ($validated['trainer_id']) {
            $query->where('user_id', $validated['trainer_id']);
        }
        
        $registrations = $query->orderBy('user_id')
                               ->orderBy('date')
                               ->orderBy('start_time')
                               ->get();
        
        $csvData = [];
        $csvData[] = ['Uren Export - ' . Carbon::create($validated['year'], $validated['month'])->format('F Y')];
        $csvData[] = ['Gegenereerd op', now()->format('d-m-Y H:i')];
        $csvData[] = [];
        $csvData[] = ['Trainer', 'Datum', 'Start', 'Eind', 'Uren', 'Type', 'Beschrijving', 'Tarief', 'Totaal', 'Status'];
        
        foreach ($registrations as $reg) {
            $csvData[] = [
                $reg->trainer->name,
                $reg->date->format('d-m-Y'),
                $reg->start_time,
                $reg->end_time,
                $reg->hours,
                $reg->type,
                $reg->description ?? $reg->lessonSchedule?->group?->name ?? '',
                $reg->hourly_rate,
                $reg->total_amount,
                $reg->status,
            ];
        }
        
        // Add summary
        $csvData[] = [];
        $csvData[] = ['Samenvatting'];
        $csvData[] = ['Totaal uren', $registrations->sum('hours')];
        $csvData[] = ['Totaal bedrag', $registrations->sum('total_amount')];
        $csvData[] = ['Goedgekeurd', $registrations->where('status', 'approved')->sum('total_amount')];
        $csvData[] = ['In afwachting', $registrations->where('status', 'pending')->sum('total_amount')];
        
        $filename = 'uren-export-' . $validated['year'] . '-' . str_pad($validated['month'], 2, '0', STR_PAD_LEFT) . '.csv';
        
        return response()->streamDownload(function() use ($csvData) {
            $file = fopen('php://output', 'w');
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        }, $filename);
    }
}
