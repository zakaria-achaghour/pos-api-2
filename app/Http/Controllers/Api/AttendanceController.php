<?php

namespace App\Http\Controllers\Api;

use App\Events\StaffClockedIn;
use App\Events\StaffClockedOut;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Staff;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Infrastructure\Tenancy\Tenant;

class AttendanceController extends Controller
{
    public function clockIn(Request $request): JsonResponse
    {
        $request->validate([
            'staff_id' => 'required|exists:staff,id',
        ]);

        $staff = Staff::where('restaurant_id', Tenant::id())
            ->findOrFail($request->staff_id);

        // Check if already clocked in
        $activeAttendance = $staff->attendances()
            ->whereNull('clock_out')
            ->first();

        if ($activeAttendance) {
            return response()->json([
                'message' => 'Staff member is already clocked in',
                'attendance' => $activeAttendance
            ], 422);
        }

        $attendance = Attendance::create([
            'staff_id' => $staff->id,
            'clock_in' => now(),
        ]);
        event(new StaffClockedIn($attendance));

        return response()->json([
            'message' => 'Clocked in successfully',
            'attendance' => $attendance
        ]);
    }

    public function clockOut(Request $request): JsonResponse
    {
        $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'break_minutes' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $staff = Staff::where('restaurant_id', Tenant::id())
            ->findOrFail($request->staff_id);

        $attendance = $staff->attendances()
            ->whereNull('clock_out')
            ->first();

        if (!$attendance) {
            return response()->json([
                'message' => 'No active clock-in found'
            ], 422);
        }

        $attendance->update([
            'clock_out' => now(),
            'break_minutes' => $request->break_minutes ?? 0,
            'notes' => $request->notes,
        ]);

        $attendance->calculateHoursWorked();
        event(new StaffClockedOut($attendance));
        return response()->json([
            'message' => 'Clocked out successfully',
            'attendance' => $attendance
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Attendance::with(['staff'])
            ->where('restaurant_id', Tenant::id());

        if ($request->has('staff_id')) {
            $query->where('staff_id', $request->staff_id);
        }

        if ($request->has('date_from')) {
            $query->where('clock_in', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('clock_in', '<=', $request->date_to);
        }

        $attendances = $query->orderBy('clock_in', 'desc')->paginate();

        return response()->json($attendances);
    }

    public function summary(Request $request): JsonResponse
    {
        $period = $request->get('period', 'week');
        $startDate = match($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            default => now()->startOfWeek(),
        };

        $attendances = Attendance::with(['staff'])
            ->where('restaurant_id', Tenant::id())
            ->where('clock_in', '>=', $startDate)
            ->whereNotNull('clock_out')
            ->get()
            ->groupBy('staff_id')
            ->map(function ($staffAttendances) {
                $staff = $staffAttendances->first()->staff;
                return [
                    'staff_id' => $staff->id,
                    'staff_name' => $staff->full_name,
                    'total_hours' => $staffAttendances->sum('hours_worked'),
                    'total_days' => $staffAttendances->count(),
                    'average_hours_per_day' => $staffAttendances->avg('hours_worked'),
                ];
            });

        return response()->json($attendances->values());
    }


    public function generateSummaryPdf(Request $request)
    {
        $request->validate([
            'period' => 'nullable|in:today,week,month,custom',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $period = $request->get('period', 'week');
        
        if ($period === 'custom') {
            $startDate = \Carbon\Carbon::parse($request->start_date)->startOfDay();
            $endDate = \Carbon\Carbon::parse($request->end_date)->endOfDay();
        } else {
            $startDate = match($period) {
                'today' => now()->startOfDay(),
                'week' => now()->startOfWeek(),
                'month' => now()->startOfMonth(),
                default => now()->startOfWeek(),
            };
            $endDate = now()->endOfDay();
        }

        $attendances = Attendance::with(['staff'])
            ->where('restaurant_id', Tenant::id())
            ->whereBetween('clock_in', [$startDate, $endDate])
            ->whereNotNull('clock_out')
            ->get()
            ->groupBy('staff_id')
            ->map(function ($staffAttendances) {
                $staff = $staffAttendances->first()->staff;
                return [
                    'staff_name' => $staff->full_name,
                    'position' => $staff->position,
                    'total_hours' => $staffAttendances->sum('hours_worked'),
                    'total_days' => $staffAttendances->count(),
                    'average_hours_per_day' => $staffAttendances->avg('hours_worked'),
                ];
            });

        $summary = [
            'total_staff' => $attendances->count(),
            'total_hours' => $attendances->sum('total_hours'),
            'average_hours' => $attendances->avg('total_hours'),
            'total_days' => $attendances->sum('total_days'),
        ];

        $pdf = Pdf::loadView('reports.pdf.attendance.summary', [
            'attendances' => $attendances,
            'summary' => $summary,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);

        $filename = 'attendance-summary-' . now()->format('Y-m-d-H-i-s') . '.pdf';

        return $pdf->download($filename);
    }

    public function generateDetailedPdf(Request $request)
    {
        $request->validate([
            'period' => 'nullable|in:today,week,month,custom',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'staff_id' => 'nullable|exists:staff,id',
        ]);

        $period = $request->get('period', 'week');
        
        if ($period === 'custom') {
            $startDate = \Carbon\Carbon::parse($request->start_date)->startOfDay();
            $endDate = \Carbon\Carbon::parse($request->end_date)->endOfDay();
        } else {
            $startDate = match($period) {
                'today' => now()->startOfDay(),
                'week' => now()->startOfWeek(),
                'month' => now()->startOfMonth(),
                default => now()->startOfWeek(),
            };
            $endDate = now()->endOfDay();
        }

        $query = Attendance::with(['staff'])
            ->where('restaurant_id', Tenant::id())
            ->whereBetween('clock_in', [$startDate, $endDate]);

        if ($request->has('staff_id')) {
            $query->where('staff_id', $request->staff_id);
        }

        $attendances = $query->orderBy('clock_in', 'desc')->get();

        $pdf = Pdf::loadView('reports.pdf.attendance.detailed', [
            'attendances' => $attendances,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);

        $filename = 'attendance-detailed-' . now()->format('Y-m-d-H-i-s') . '.pdf';

        return $pdf->download($filename);
    }
}