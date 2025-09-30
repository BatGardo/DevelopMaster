<?php

namespace App\Services\Reports;

use App\Models\CaseAction;
use App\Models\CaseDocument;
use App\Models\CaseModel;
use App\Models\User;
use App\Support\AnalyticsCaseFilter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsExcelExporter
{
    public function download(array $filters): StreamedResponse
    {
        $summary = $this->buildSummary($filters);
        $cases = $this->buildCases($filters);
        $generatedAt = Carbon::now();

        $content = view('analytics.export', [
            'summary' => $summary,
            'cases' => $cases,
            'generatedAt' => $generatedAt,
        ])->render();

        $filename = 'analytics-records-' . $generatedAt->format('Ymd-His') . '.xls';

        return response()->streamDownload(function () use ($content) {
            echo "Р вЂњР вЂЎР вЂ™Р’В»Р вЂ™РЎвЂ”" . $content;
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    protected function buildCases(array $filters): Collection
    {
        $query = CaseModel::query()
            ->with(['owner', 'executor'])
            ->withCount(['actions', 'documents'])
            ->withMax('actions', 'created_at');

        AnalyticsCaseFilter::apply($query, $filters);

        return $query
            ->orderByDesc('created_at')
            ->get()
            ->map(function (CaseModel $case) {
                return [
                    'id' => $case->id,
                    'title' => $case->title,
                    'status' => __('statuses.' . $case->status),
                    'owner' => $case->owner?->name ?? __('Unknown'),
                    'executor' => $case->executor?->name ?? __('Unassigned'),
                    'created_at' => $case->created_at?->format('Y-m-d H:i'),
                    'deadline_at' => $case->deadline_at?->format('Y-m-d'),
                    'actions' => (int) ($case->actions_count ?? 0),
                    'documents' => (int) ($case->documents_count ?? 0),
                    'last_activity' => $case->actions_max_created_at
                        ? Carbon::parse($case->actions_max_created_at)->format('Y-m-d H:i')
                        : null,
                ];
            });
    }

    protected function buildSummary(array $filters): array
    {
        $baseQuery = CaseModel::query();
        AnalyticsCaseFilter::apply($baseQuery, $filters);

        $totalCases = (clone $baseQuery)->count();

        $totalActions = CaseAction::whereHas('case', function ($query) use ($filters) {
            AnalyticsCaseFilter::apply($query, $filters);
        })->count();

        $totalDocuments = CaseDocument::whereHas('case', function ($query) use ($filters) {
            AnalyticsCaseFilter::apply($query, $filters);
        })->count();

        $avgActionsPerCase = $totalCases ? round($totalActions / $totalCases, 2) : 0;
        $avgDocumentsPerCase = $totalCases ? round($totalDocuments / $totalCases, 2) : 0;

        $statusSummary = (clone $baseQuery)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->orderBy('status')
            ->get()
            ->map(fn ($row) => ['label' => __('statuses.' . $row->status), 'total' => (int) $row->total])
            ->toArray();

        $executorRows = (clone $baseQuery)
            ->select('executor_id', DB::raw('COUNT(*) as total'))
            ->groupBy('executor_id')
            ->orderByDesc('total')
            ->take(10)
            ->get();

        $executorNames = User::whereIn('id', $executorRows->pluck('executor_id')->filter())
            ->pluck('name', 'id');

        $executorSummary = $executorRows->map(function ($row) use ($executorNames) {
            $name = $row->executor_id ? ($executorNames[$row->executor_id] ?? __('Unassigned')) : __('Unassigned');
            return ['label' => $name, 'total' => (int) $row->total];
        })->toArray();

        $dailySeries = (clone $baseQuery)
            ->selectRaw("date(created_at) as day, COUNT(*) as total")
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($row) => ['label' => $row->day, 'total' => (int) $row->total])
            ->toArray();

        $actionTypes = CaseAction::select('type', DB::raw('COUNT(*) as total'))
            ->whereHas('case', function ($query) use ($filters) {
                AnalyticsCaseFilter::apply($query, $filters);
            })
            ->groupBy('type')
            ->orderByDesc('total')
            ->take(10)
            ->get()
            ->map(fn ($row) => ['label' => __('actions.' . $row->type), 'total' => (int) $row->total])
            ->toArray();

        $documentExtensions = CaseDocument::selectRaw("COALESCE(lower(split_part(path, '.', -1)), 'unknown') as extension, COUNT(*) as total")
            ->whereHas('case', function ($query) use ($filters) {
                AnalyticsCaseFilter::apply($query, $filters);
            })
            ->groupBy('extension')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => ['label' => $row->extension, 'total' => (int) $row->total])
            ->toArray();

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

        return [
            'metrics' => [
                ['label' => __('Cases in scope'), 'value' => $totalCases],
                ['label' => __('Average actions per case'), 'value' => $avgActionsPerCase],
                ['label' => __('Average documents per case'), 'value' => $avgDocumentsPerCase],
                ['label' => __('Overdue cases'), 'value' => $overdueCases],
                ['label' => __('On-track cases'), 'value' => $onTrackCases],
                ['label' => __('Deadline lead (days)'), 'value' => $deadlineLead ? round($deadlineLead, 1) : __('N/A')],
            ],
            'status' => $statusSummary,
            'executors' => $executorSummary,
            'daily' => $dailySeries,
            'actions' => $actionTypes,
            'documents' => $documentExtensions,
        ];
    }
}
