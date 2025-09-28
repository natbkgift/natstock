<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

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
            $driver = (new Activity())->getConnection()->getDriverName();
            $likeKeyword = '%'.$keyword.'%';

            $query->where(function ($subQuery) use ($driver, $likeKeyword) {
                $subQuery->where('description', 'like', $likeKeyword);

                switch ($driver) {
                    case 'mysql':
                    case 'mariadb':
                        $subQuery->orWhereRaw("JSON_SEARCH(properties, 'one', ?, NULL, '$**') IS NOT NULL", [$likeKeyword]);

                        break;
                    case 'pgsql':
                        $subQuery->orWhereRaw('properties::jsonb::text ILIKE ?', [$likeKeyword]);

                        break;
                    default:
                        $subQuery->orWhere('properties', 'like', $likeKeyword);

                        break;
                }
            });
        }

        if ($dateFrom) {
            try {
                $from = Carbon::createFromFormat('Y-m-d', $dateFrom)->startOfDay();
                $query->where('happened_at', '>=', $from);
            } catch (\Exception $exception) {
                Log::channel('daily')->debug('Invalid date format for date_from filter', [
                    'date_from' => $dateFrom,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        if ($dateTo) {
            try {
                $to = Carbon::createFromFormat('Y-m-d', $dateTo)->endOfDay();
                $query->where('happened_at', '<=', $to);
            } catch (\Exception $exception) {
                Log::channel('daily')->debug('Invalid date format for date_to filter', [
                    'date_to' => $dateTo,
                    'error' => $exception->getMessage(),
                ]);
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
