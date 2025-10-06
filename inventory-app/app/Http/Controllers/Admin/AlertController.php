<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserAlertState;
use App\Services\AlertSnapshotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class AlertController extends Controller
{
    public function __construct(private readonly AlertSnapshotService $snapshots)
    {
    }

    public function markRead(Request $request): JsonResponse
    {
        Gate::authorize('access-viewer');

        $data = $this->validatePayload($request);

        $this->updateState(
            $request->user()->id,
            $data['type'],
            $data['payload_hash'],
            [
                'read_at' => Carbon::now(),
                'snooze_until' => null,
            ]
        );

        return response()->json(['status' => 'ok']);
    }

    public function snooze(Request $request): JsonResponse
    {
        Gate::authorize('access-viewer');

        $data = $this->validatePayload($request);

        $snoozeUntil = Carbon::now()->endOfDay();
        $userId = $request->user()->id;

        $this->updateState(
            $userId,
            $data['type'],
            $data['payload_hash'],
            [
                'snooze_until' => $snoozeUntil,
                'read_at' => null,
            ]
        );

        $snapshot = $this->snapshots->buildSnapshot();

        foreach (['low_stock', 'expiring'] as $type) {
            if ($type === $data['type']) {
                continue;
            }

            $payloadHash = $snapshot[$type]['payload_hash'] ?? null;

            if (! $snapshot[$type]['enabled'] || $snapshot[$type]['count'] <= 0 || ! $payloadHash) {
                continue;
            }

            $this->updateState(
                $userId,
                $type,
                $payloadHash,
                [
                    'snooze_until' => $snoozeUntil,
                    'read_at' => null,
                ]
            );
        }

        return response()->json([
            'status' => 'ok',
            'snooze_until' => $snoozeUntil->toDateTimeString(),
        ]);
    }

    /**
     * @return array{type: string, payload_hash: string}
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'type' => ['required', 'in:low_stock,expiring'],
            'payload_hash' => ['required', 'string', 'min:10', 'max:64'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function updateState(int $userId, string $type, string $payloadHash, array $values): void
    {
        UserAlertState::query()->updateOrCreate(
            [
                'user_id' => $userId,
                'alert_type' => $type,
                'payload_hash' => $payloadHash,
            ],
            $values
        );
    }
}
