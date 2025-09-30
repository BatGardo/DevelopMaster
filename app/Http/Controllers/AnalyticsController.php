<?php

namespace App\Http\Controllers;

use App\Models\CaseAction;
use App\Models\CaseDocument;
use App\Models\CaseModel;
use App\Models\User;
use App\Services\Reports\AnalyticsExcelExporter;
use App\Support\AnalyticsCaseFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index()
    {
        $byStatus = CaseModel::select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->orderBy('status')
            ->get()
            ->mapWithKeys(fn ($row) => [__('statuses.' . $row->status) => (int) $row->total]);

        $executorLoad = CaseModel::select('executor_id', DB::raw('COUNT(*) as total'))
            ->groupBy('executor_id')
            ->orderByDesc('total')
            ->take(10)
            ->get()
            ->map(function ($row) {
                $name = optional(User::find($row->executor_id))->name ?? __('Unassigned');
                return ['name' => $name, 'total' => (int) $row->total];
            });

        $overdue = CaseModel::whereIn('status', ['new', 'in_progress'])
            ->whereNotNull('deadline_at')
            ->where('deadline_at', '<', now())
            ->count();

        $onTime = CaseModel::whereNotNull('deadline_at')
            ->where('deadline_at', '>=', now())
            ->count();

        $trend = CaseModel::selectRaw("to_char(date_trunc('month', created_at), 'YYYY-MM') as label, COUNT(*) as total")
            ->where('created_at', '>=', now()->startOfMonth()->subMonths(11))
            ->groupBy('label')
            ->orderBy('label')
            ->pluck('total', 'label');

        $topApplicants = CaseModel::select('user_id', DB::raw('COUNT(*) as total'))
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->take(5)
            ->get()
            ->map(function ($row) {
                $user = optional(User::find($row->user_id));
                return [
                    'name' => $user?->name ?? __('Unknown'),
                    'total' => (int) $row->total,
                ];
            });

        $olap = $this->loadOlapSummary();

        return view('analytics', compact(
            'byStatus',
            'executorLoad',
            'onTime',
            'overdue',
            'trend',
            'topApplicants',
            'olap'
        ));
    }

    public function records(Request $request)
    {
        $filterData = $this->prepareFilters($request);
        $normalizedFilters = $filterData['normalized'];
        $filtersForView = $filterData['view'];

        $baseQuery = CaseModel::query()->with(['owner', 'executor']);
        AnalyticsCaseFilter::apply($baseQuery, $normalizedFilters);


        $casesQuery = (clone $baseQuery)
            ->withCount(['actions', 'documents'])
            ->withMax('actions', 'created_at');

        $cases = $casesQuery
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $totalCases = (clone $baseQuery)->count();
        $totalActions = CaseAction::whereHas('case', function ($query) use ($normalizedFilters) {
            AnalyticsCaseFilter::apply($query, $normalizedFilters);
        })->count();
        $totalDocuments = CaseDocument::whereHas('case', function ($query) use ($normalizedFilters) {
            AnalyticsCaseFilter::apply($query, $normalizedFilters);
        })->count();

        $avgActionsPerCase = $totalCases ? round($totalActions / $totalCases, 2) : 0;
        $avgDocumentsPerCase = $totalCases ? round($totalDocuments / $totalCases, 2) : 0;

        $statusSummary = (clone $baseQuery)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->orderBy('status')
            ->get()
            ->map(function ($row) {
                return [
                    'status' => __('statuses.' . $row->status),
                    'total' => (int) $row->total,
                ];
            });

        $executorSummaryRows = (clone $baseQuery)
            ->select('executor_id', DB::raw('COUNT(*) as total'))
            ->groupBy('executor_id')
            ->orderByDesc('total')
            ->take(10)
            ->get();

        $executorNames = User::whereIn('id', $executorSummaryRows->pluck('executor_id')->filter())
            ->pluck('name', 'id');

        $executorSummary = $executorSummaryRows->map(function ($row) use ($executorNames) {
            $name = $row->executor_id ? ($executorNames[$row->executor_id] ?? __('Unassigned')) : __('Unassigned');
            return ['name' => $name, 'total' => (int) $row->total];
        });

        $dailySeries = (clone $baseQuery)
            ->selectRaw("date(created_at) as day, COUNT(*) as total")
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $actionTypes = CaseAction::select('type', DB::raw('COUNT(*) as total'))
            ->whereHas('case', function ($query) use ($normalizedFilters) {
                AnalyticsCaseFilter::apply($query, $normalizedFilters);
            })
            ->groupBy('type')
            ->orderByDesc('total')
            ->take(10)
            ->get();

        $documentExtensions = CaseDocument::selectRaw("COALESCE(lower(split_part(path, '.', -1)), 'unknown') as extension, COUNT(*) as total")
            ->whereHas('case', function ($query) use ($normalizedFilters) {
                AnalyticsCaseFilter::apply($query, $normalizedFilters);
            })
            ->groupBy('extension')
            ->orderByDesc('total')
            ->get();

        $overdueCases = (clone $baseQuery)
            ->whereNotNull('deadline_at')
            ->where('deadline_at', '<', now())
            ->count();

        $onTrackCases = (clone $baseQuery)
            ->where(function ($query) {
                $query->whereNull('deadline_at')
                    ->orWhere('deadline_at', '>=', now());
            })
            ->count();

        $deadlineLead = (clone $baseQuery)
            ->whereNotNull('deadline_at')
            ->selectRaw("AVG(EXTRACT(EPOCH FROM (deadline_at - created_at)) / 86400) as avg_days")
            ->value('avg_days');

        $filterOptions = [
            'statuses' => CaseModel::statusOptions(),
            'executors' => User::whereIn('role', ['executor', 'admin'])->orderBy('name')->get(),
            'owners' => User::orderBy('name')->get(),
        ];

        $olap = $this->loadOlapSummary();

        return view('analytics.details', [
            'cases' => $cases,
            'totalCases' => $totalCases,
            'avgActionsPerCase' => $avgActionsPerCase,
            'avgDocumentsPerCase' => $avgDocumentsPerCase,
            'statusSummary' => $statusSummary,
            'executorSummary' => $executorSummary,
            'dailySeries' => $dailySeries,
            'actionTypes' => $actionTypes,
            'documentExtensions' => $documentExtensions,
            'overdueCases' => $overdueCases,
            'onTrackCases' => $onTrackCases,
            'deadlineLead' => $deadlineLead,
            'filterOptions' => $filterOptions,
            'filters' => $filtersForView,
            'olap' => $olap,
        ]);
    }

    public function exportRecords(Request $request, AnalyticsExcelExporter $exporter)
    {
        $filterData = $this->prepareFilters($request);

        return $exporter->download($filterData['normalized']);
    }

    protected function prepareFilters(Request $request): array
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'max:50'],
            'executor' => ['nullable', 'integer', 'exists:users,id'],
            'owner' => ['nullable', 'integer', 'exists:users,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $status = $validated['status'] ?? null;
        $executor = $validated['executor'] ?? null;
        $owner = $validated['owner'] ?? null;

        if ($status === '') {
            $status = null;
        }
        if ($executor === '') {
            $executor = null;
        }
        if ($owner === '') {
            $owner = null;
        }

        $hasDateFilter = $request->filled('date_from') || $request->filled('date_to');
        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->input('date_from'))->startOfDay()
            : ($hasDateFilter ? null : Carbon::now()->subDays(90)->startOfDay());
        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->input('date_to'))->endOfDay()
            : null;

        return [
            'normalized' => [
                'status' => $status,
                'executor' => $executor,
                'owner' => $owner,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'view' => [
                'status' => $status,
                'executor' => $executor,
                'owner' => $owner,
                'date_from' => $dateFrom?->toDateString(),
                'date_to' => $dateTo?->toDateString(),
            ],
        ];
    }

    protected function loadOlapSummary(): array
    {
        try {
            $connection = DB::connection(config('olap.connection'));

            $logins = $connection->table('fact_user_logins')
                ->selectRaw("date(to_date(date_key, 'YYYYMMDD')) as metric_date, SUM(login_count) as total")
                ->groupBy('metric_date')
                ->orderBy('metric_date')
                ->take(30)
                ->get();

            $registrations = $connection->table('fact_user_registrations')
                ->selectRaw("date(to_date(date_key, 'YYYYMMDD')) as metric_date, COUNT(*) as total")
                ->groupBy('metric_date')
                ->orderBy('metric_date')
                ->take(30)
                ->get();

            return [
                'enabled' => true,
                'logins' => $logins,
                'registrations' => $registrations,
            ];
        } catch (\Throwable $e) {
            return [
                'enabled' => false,
                'logins' => collect(),
                'registrations' => collect(),
            ];
        }
    }
}