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
use Illuminate\Support\Str;

class AnalyticsController extends Controller
{
    public function index()
    {
        $statusTotals = CaseModel::select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->orderBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $statusLabels = CaseModel::statusOptions();

        $byStatus = collect($statusTotals)
            ->mapWithKeys(fn ($count, $status) => [$statusLabels[$status] ?? $status => (int) $count]);

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

        $casesForAnalysis = CaseModel::select('id', 'title', 'created_at', 'deadline_at')
            ->whereNotNull('created_at')
            ->get();

        $seasonalitySeriesCollection = $casesForAnalysis
            ->groupBy(fn (CaseModel $case) => (int) $case->created_at->format('n'))
            ->sortKeys()
            ->map(function ($group, $monthNumber) {
                $monthDate = Carbon::create((int) now()->format('Y'), $monthNumber, 1);
                $monthName = $monthDate->locale(app()->getLocale())->isoFormat('MMMM');
                $normalizedName = Str::ucfirst(Str::lower($monthName));
                $total = $group->count();

                $leadValues = $group->map(function (CaseModel $case) {
                    return $case->deadline_at ? $case->deadline_at->diffInDays($case->created_at) : null;
                })->filter();

                $avgLeadRaw = $leadValues->isNotEmpty() ? $leadValues->avg() : null;
                $avgLead = $avgLeadRaw !== null ? round(max($avgLeadRaw, 0), 1) : null;

                return [
                    'month' => $normalizedName,
                    'total' => $total,
                    'avg_lead' => $avgLead,
                ];
            })
            ->values();

        $seasonalityAverage = $seasonalitySeriesCollection->avg('total') ?? 0;
        $seasonalityPeak = $seasonalitySeriesCollection->sortByDesc('total')->first();
        $seasonalityTrough = $seasonalitySeriesCollection->sortBy('total')->first();
        $seasonalitySummary = [
            'average' => round($seasonalityAverage, 1),
            'peak' => $seasonalityPeak,
            'trough' => $seasonalityTrough,
            'above' => $seasonalitySeriesCollection
                ->filter(fn ($row) => $row['total'] > $seasonalityAverage)
                ->map(fn ($row) => $row['month'])
                ->unique()
                ->values(),
        ];
        $seasonalitySeries = $seasonalitySeriesCollection
            ->map(fn ($row) => [
                'month' => $row['month'],
                'total' => $row['total'],
                'avgLead' => $row['avg_lead'],
            ])
            ->toArray();

        $regionStats = $casesForAnalysis
            ->groupBy(fn (CaseModel $case) => $this->extractRegionFromTitle($case->title))
            ->map(function ($group, $region) {
                $leadValues = $group->map(function (CaseModel $case) {
                    return $case->deadline_at ? $case->deadline_at->diffInDays($case->created_at) : null;
                })->filter();

                $avgLeadRaw = $leadValues->isNotEmpty() ? $leadValues->avg() : null;
                $avgLead = $avgLeadRaw !== null ? round(max($avgLeadRaw, 0), 1) : null;

                return [
                    'region' => $region,
                    'total' => $group->count(),
                    'avg_lead' => $avgLead,
                ];
            })
            ->sortByDesc('total')
            ->values();

        $regionBreakdown = $regionStats->toArray();
        $regionVelocity = $regionStats
            ->mapWithKeys(fn ($row) => [$row['region'] => $row['avg_lead']])
            ->toArray();
        $regionSummary = [
            'top' => $regionStats->first(fn ($row) => ($row['region'] ?? __('Not specified')) !== __('Not specified')) ?? $regionStats->first(),
            'slow' => $regionStats
                ->filter(fn ($row) => $row['avg_lead'] !== null)
                ->sortByDesc('avg_lead')
                ->first(),
        ];

        $olap = $this->loadOlapSummary();

        return view('analytics', compact(
            'statusTotals',
            'statusLabels',
            'byStatus',
            'executorLoad',
            'onTime',
            'overdue',
            'trend',
            'topApplicants',
            'olap',
            'seasonalitySeries',
            'seasonalitySummary',
            'regionBreakdown',
            'regionVelocity',
            'regionSummary'
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
            $name = $executorNames[$row->executor_id] ?? __('Unassigned');

            return [
                'executor' => $name,
                'total' => (int) $row->total,
            ];
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
            ->orderBy('type')
            ->get()
            ->map(function ($row) {
                $translationKey = $row->type ? 'actions.' . $row->type : null;
                $label = $translationKey ? __($translationKey) : __('Unknown');

                if ($translationKey && $label === $translationKey) {
                    $label = Str::of($row->type)->replace('_', ' ')->title();
                }

                return [
                    'type' => $label,
                    'total' => (int) $row->total,
                ];
            });

        $documentExtensions = CaseDocument::select(DB::raw("lower(substring(title from '\\.\\w+$')) as ext"), DB::raw('COUNT(*) as total'))
            ->whereHas('case', function ($query) use ($normalizedFilters) {
                AnalyticsCaseFilter::apply($query, $normalizedFilters);
            })
            ->groupBy('ext')
            ->orderByDesc('total')
            ->get()
            ->map(function ($row) {
                $extension = $row->ext ? ltrim($row->ext, '.') : __('Unknown');

                return (object) [
                    'extension' => $extension,
                    'total' => (int) $row->total,
                ];
            });

        $overdueCases = (clone $baseQuery)
            ->whereIn('status', ['new', 'in_progress'])
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

    protected function extractRegionFromTitle(string $title): string
    {
        if (preg_match('/\\(([^,]+),/u', $title, $matches)) {
            $region = Str::ucfirst(Str::lower(trim($matches[1])));

            return $region;
        }

        return __('Not specified');
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