<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashierShift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Infrastructure\Tenancy\Tenant;

class CashierShiftController extends Controller
{
    public function current(Request $request): JsonResponse
    {
        $user = $request->user('api');
        $restaurantId = Tenant::id();

        $shift = $restaurantId
            ? CashierShift::where('restaurant_id', $restaurantId)
                ->where('user_id', $user->id)
                ->whereNull('closed_at')
                ->first()
            : null;

        return response()->json([
            'shift' => $shift,
        ]);
    }

    public function open(Request $request): JsonResponse
    {
        $user = $request->user('api');
        $restaurantId = Tenant::id();
        abort_unless($restaurantId, 400, 'Restaurant context is required.');

        $hasOpenShift = CashierShift::where('restaurant_id', $restaurantId)
            ->where('user_id', $user->id)
            ->whereNull('closed_at')
            ->exists();

        if ($hasOpenShift) {
            return response()->json([
                'message' => 'You already have an open shift.',
            ], 422);
        }

        $data = $request->validate([
            'opening_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $shift = CashierShift::create([
            'restaurant_id' => $restaurantId,
            'user_id' => $user->id,
            'opened_at' => now(),
            'opening_amount' => $data['opening_amount'] ?? 0,
            'notes' => $data['notes'] ?? null,
        ]);

        return response()->json([
            'message' => 'Shift opened successfully',
            'shift' => $shift,
        ], 201);
    }

    public function close(Request $request): JsonResponse
    {
        $user = $request->user('api');
        $restaurantId = Tenant::id();
        abort_unless($restaurantId, 400, 'Restaurant context is required.');

        $shift = CashierShift::where('restaurant_id', $restaurantId)
            ->where('user_id', $user->id)
            ->whereNull('closed_at')
            ->first();

        if (!$shift) {
            return response()->json([
                'message' => 'No open shift to close.',
            ], 422);
        }

        $data = $request->validate([
            'closing_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $shift->update([
            'closing_amount' => $data['closing_amount'] ?? $shift->closing_amount,
            'notes' => $data['notes'] ?? $shift->notes,
            'closed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Shift closed successfully',
            'shift' => $shift->fresh(),
        ]);
    }
}
