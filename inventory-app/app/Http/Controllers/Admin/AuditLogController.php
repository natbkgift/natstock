<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('access-admin');

        $action = $request->string('action')->toString();
        $keyword = trim((string) $request->input('keyword', ''));
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = Activity::query()->with('actor')->orderByDesc('happened_at');

        if ($action !== '') {
            $query->where('action', $action);
        }

        if ($keyword !== '') {
            $query->where(function ($subQuery) use ($keyword) {
                $subQuery->where('description', 'like', "%{$keyword}%")
                    ->orWhere('properties', 'like', "%{$keyword}%");
            });
        }

        if ($dateFrom) {
            try {
                $from = Carbon::createFromFormat('Y-m-d', $dateFrom)->startOfDay();
                $query->where('happened_at', '>=', $from);
            } catch (\Exception $exception) {
                // ignore invalid date format
            }
        }

        if ($dateTo) {
            try {
                $to = Carbon::createFromFormat('Y-m-d', $dateTo)->endOfDay();
                $query->where('happened_at', '<=', $to);
            } catch (\Exception $exception) {
                // ignore invalid date format
            }
        }

        /** @var LengthAwarePaginator $activities */
        $activities = $query->paginate(20)->appends($request->query());

        $actions = Activity::query()->select('action')->distinct()->orderBy('action')->pluck('action');

        return view('admin.audit.index', [
            'activities' => $activities,
            'filters' => [
                'action' => $action,
                'keyword' => $keyword,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'actions' => $actions,
        ]);
    }
}
