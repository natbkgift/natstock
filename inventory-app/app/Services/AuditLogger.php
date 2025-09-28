<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;

class AuditLogger
{
    public function log(string $action, ?string $description = null, array $properties = [], ?Model $subject = null, ?User $actor = null): void
    {
        $actor = $actor ?? Auth::user();
        $request = app()->runningInConsole() ? null : request();

        Activity::create([
            'action' => $action,
            'description' => $description,
            'actor_id' => $actor?->getKey(),
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'properties' => $this->prepareProperties($properties, $request),
            'ip_address' => $request?->ip(),
            'happened_at' => now(),
        ]);
    }

    private function prepareProperties(array $properties, ?Request $request): array
    {
        if ($request) {
            $sanitizedRequest = Arr::except($request->all(), ['password', 'password_confirmation', 'token']);

            if ($sanitizedRequest !== []) {
                $properties['request'] = $sanitizedRequest;
            }

            if ($userAgent = $request->userAgent()) {
                $properties['user_agent'] = substr($userAgent, 0, 255);
            }
        }

        return $properties;
    }
}
