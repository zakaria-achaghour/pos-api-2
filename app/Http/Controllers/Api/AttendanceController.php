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
    /**
     * @OA\Post(
     *     path="/api/staff/attendance/clock-in",
     *     tags={"Attendance"},
     *     summary="Clock in staff member",
     *     description="Record staff member clock-in time and create attendance record",
     *     security={{"bearer_token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Clock-in data",
     *         @OA\JsonContent(
     *             required={"staff_id"},
     *             @OA\Property(property="staff_id", type="integer", example=5, description="ID of the staff member")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Clocked in successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Clocked in successfully"),
     *             @OA\Property(property="attendance", ref="#/components/schemas/Attendance")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Staff member is already clocked in",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Staff member is already clocked in"),
     *             @OA\Property(property="attendance", ref="#/components/schemas/Attendance")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/staff/attendance/clock-out",
     *     tags={"Attendance"},
     *     summary="Clock out staff member",
     *     description="Record staff member clock-out time and calculate hours worked",
     *     security={{"bearer_token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Clock-out data",
     *         @OA\JsonContent(
     *             required={"staff_id"},
     *             @OA\Property(property="staff_id", type="integer", example=5, description="ID of the staff member"),
     *             @OA\Property(property="break_minutes", type="integer", example=30, description="Break time in minutes"),
     *             @OA\Property(property="notes", type="string", example="Worked overtime", description="Additional notes")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Clocked out successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Clocked out successfully"),
     *             @OA\Property(property="attendance", ref="#/components/schemas/Attendance")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="No active clock-in found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="No active clock-in found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/staff/attendance",
     *     tags={"Attendance"},
     *     summary="Get attendance records",
     *     description="Retrieve paginated list of attendance records with optional filtering",
     *     security={{"bearer_token": {}}},
     *     @OA\Parameter(
     *         name="staff_id",
     *         in="query",
     *         description="Filter by staff member ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="date_from",
     *         in="query",
     *         description="Filter records from this date",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="date_to",
     *         in="query",
     *         description="Filter records until this date",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-01-31")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Attendance records retrieved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/PaginatedResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/staff/attendance/summary",
     *     tags={"Attendance"},
     *     summary="Get attendance summary",
     *     description="Retrieve attendance summary grouped by staff member for specified period",
     *     security={{"bearer_token": {}}},
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         description="Time period for summary",
     *         required=false,
     *         @OA\Schema(type="string", enum={"today", "week", "month"}, example="week")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Attendance summary retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="staff_id", type="integer", example=5),
     *                 @OA\Property(property="staff_name", type="string", example="John Doe"),
     *                 @OA\Property(property="total_hours", type="number", format="float", example=40.5),
     *                 @OA\Property(property="total_days", type="integer", example=5),
     *                 @OA\Property(property="average_hours_per_day", type="number", format="float", example=8.1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
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


    /**
     * @OA\Get(
     *     path="/api/staff/attendance/reports/summary-pdf",
     *     tags={"Attendance"},
     *     summary="Generate attendance summary PDF report",
     *     description="Generate and download PDF report of attendance summary",
     *     security={{"bearer_token": {}}},
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         description="Time period for report",
     *         required=false,
     *         @OA\Schema(type="string", enum={"today", "week", "month", "custom"}, example="week")
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Start date for custom period",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="End date for custom period",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-01-31")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="PDF report generated successfully",
     *         @OA\MediaType(
     *             mediaType="application/pdf",
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/staff/attendance/reports/detailed-pdf",
     *     tags={"Attendance"},
     *     summary="Generate detailed attendance PDF report",
     *     description="Generate and download detailed PDF report of attendance records",
     *     security={{"bearer_token": {}}},
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         description="Time period for report",
     *         required=false,
     *         @OA\Schema(type="string", enum={"today", "week", "month", "custom"}, example="week")
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Start date for custom period",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="End date for custom period",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-01-31")
     *     ),
     *     @OA\Parameter(
     *         name="staff_id",
     *         in="query",
     *         description="Filter by specific staff member",
     *         required=false,
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="PDF report generated successfully",
     *         @OA\MediaType(
     *             mediaType="application/pdf",
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
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