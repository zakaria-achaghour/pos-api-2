<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateScheduleRequest;
use App\Http\Requests\UpdateScheduleRequest;
use App\Models\Schedule;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Infrastructure\Tenancy\Tenant;

class ScheduleController extends Controller
{
    public function __construct()
    {
        // $this->middleware(['role:Owner|Manager'])->except(['index', 'show']);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Schedule::with(['staff'])
            ->where('restaurant_id', Tenant::id());

        if ($request->has('staff_id')) {
            $query->where('staff_id', $request->staff_id);
        }

        if ($request->has('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        if ($request->has('shift_type')) {
            $query->where('shift_type', $request->shift_type);
        }

        $schedules = $query->orderBy('date')->orderBy('start_time')->paginate();

        return response()->json($schedules);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'shift_type' => 'required|in:morning,afternoon,evening,night',
            'notes' => 'nullable|string|max:500',
        ]);
        
        $data = $request->all();
        $data['restaurant_id'] = Tenant::id();
        
        // Check for conflicts
        $conflict = Schedule::where('restaurant_id', Tenant::id())
            ->where('staff_id', $data['staff_id'])
            ->where('date', $data['date'])
            ->exists();

        if ($conflict) {
            return response()->json([
                'message' => 'Staff member already has a schedule for this date'
            ], 422);
        }

        $schedule = Schedule::create($data);
        $schedule->load('staff');

        return response()->json($schedule, 201);
    }

    public function show(Schedule $schedule): JsonResponse
    {
        // Basic authorization - ensure schedule belongs to current tenant
        if ($schedule->restaurant_id !== Tenant::id()) {
            abort(404);
        }
        
        $schedule->load('staff');

        return response()->json($schedule);
    }

    public function update(Request $request, Schedule $schedule): JsonResponse
    {
        // Basic authorization - ensure schedule belongs to current tenant
        if ($schedule->restaurant_id !== Tenant::id()) {
            abort(404);
        }
        
        $request->validate([
            'staff_id' => 'sometimes|exists:staff,id',
            'date' => 'sometimes|date',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
            'shift_type' => 'sometimes|in:morning,afternoon,evening,night',
            'notes' => 'nullable|string|max:500',
        ]);
        
        $data = $request->all();
        
        // Check for conflicts if changing staff or date
        if (($data['staff_id'] ?? $schedule->staff_id) !== $schedule->staff_id || 
            ($data['date'] ?? $schedule->date) !== $schedule->date) {
            
            $conflict = Schedule::where('restaurant_id', Tenant::id())
                ->where('staff_id', $data['staff_id'] ?? $schedule->staff_id)
                ->where('date', $data['date'] ?? $schedule->date)
                ->where('id', '!=', $schedule->id)
                ->exists();

            if ($conflict) {
                return response()->json([
                    'message' => 'Staff member already has a schedule for this date'
                ], 422);
            }
        }

        $schedule->update($data);
        $schedule->load('staff');

        return response()->json($schedule);
    }

    public function destroy(Schedule $schedule): JsonResponse
    {
        // Basic authorization - ensure schedule belongs to current tenant
        if ($schedule->restaurant_id !== Tenant::id()) {
            abort(404);
        }
        
        $schedule->delete();

        return response()->json(['message' => 'Schedule deleted successfully']);
    }

    public function weekly(Request $request): JsonResponse
    {
        $request->validate([
            'week_start' => 'nullable|date',
            'staff_id' => 'nullable|exists:staff,id',
        ]);

        $weekStart = $request->get('week_start') ? 
            \Carbon\Carbon::parse($request->get('week_start'))->startOfWeek() :
            now()->startOfWeek();
        
        $weekEnd = $weekStart->copy()->endOfWeek();

        $query = Schedule::with(['staff'])
            ->where('restaurant_id', Tenant::id())
            ->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()]);

        if ($request->has('staff_id')) {
            $query->where('staff_id', $request->staff_id);
        }

        $schedules = $query->orderBy('date')->orderBy('start_time')->get();

        // Group by day of week
        $weeklySchedule = collect(range(0, 6))->mapWithKeys(function ($dayOfWeek) use ($weekStart, $schedules) {
            $date = $weekStart->copy()->addDays($dayOfWeek);
            $daySchedules = $schedules->filter(function ($schedule) use ($date) {
                return $schedule->date->equalTo($date);
            });

            return [
                $date->format('Y-m-d') => [
                    'date' => $date->format('Y-m-d'),
                    'day_name' => $date->format('l'),
                    'schedules' => $daySchedules->values(),
                ]
            ];
        });

        return response()->json([
            'week_start' => $weekStart->format('Y-m-d'),
            'week_end' => $weekEnd->format('Y-m-d'),
            'weekly_schedule' => $weeklySchedule,
        ]);
    }

    public function bulk(Request $request): JsonResponse
    {
        $request->validate([
            'schedules' => 'required|array',
            'schedules.*.staff_id' => 'required|exists:staff,id',
            'schedules.*.date' => 'required|date',
            'schedules.*.start_time' => 'required|date_format:H:i',
            'schedules.*.end_time' => 'required|date_format:H:i|after:schedules.*.start_time',
            'schedules.*.shift_type' => 'required|in:morning,afternoon,evening,night',
            'schedules.*.notes' => 'nullable|string|max:500',
        ]);

        $createdSchedules = [];
        $errors = [];

        foreach ($request->schedules as $index => $scheduleData) {
            // Check for conflicts
            $conflict = Schedule::where('restaurant_id', Tenant::id())
                ->where('staff_id', $scheduleData['staff_id'])
                ->where('date', $scheduleData['date'])
                ->exists();

            if ($conflict) {
                $errors[] = [
                    'index' => $index,
                    'message' => 'Staff member already has a schedule for this date',
                    'data' => $scheduleData
                ];
                continue;
            }

            $schedule = Schedule::create(array_merge($scheduleData, [
                'restaurant_id' => Tenant::id()
            ]));
            
            $schedule->load('staff');
            $createdSchedules[] = $schedule;
        }

        return response()->json([
            'message' => 'Bulk schedule creation completed',
            'created' => count($createdSchedules),
            'errors' => count($errors),
            'schedules' => $createdSchedules,
            'failed' => $errors,
        ]);
    }
}