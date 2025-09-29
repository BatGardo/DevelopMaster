<?php

namespace App\Http\Controllers;

use App\Models\CaseAction;
use App\Models\CaseModel;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index()
    {
        $posts = Post::query()->latest()->take(6)->get();

        return view('welcome', compact('posts'));
    }

    public function dashboard()
    {
        $user = Auth::user();
        $role = $user->role ?? 'guest';

        $baseStats = [
            'totalCases' => CaseModel::count(),
            'openCases' => CaseModel::whereIn('status', ['new', 'in_progress'])->count(),
            'closedCases' => CaseModel::whereIn('status', ['done', 'closed'])->count(),
            'overdueCases' => CaseModel::whereIn('status', ['new', 'in_progress'])
                ->whereNotNull('deadline_at')
                ->where('deadline_at', '<', now())
                ->count(),
        ];

        $olapMetrics = $this->loadOlapMetrics();

        $payload = match ($role) {
            'admin' => $this->dashboardForAdmin($baseStats, $olapMetrics),
            'executor' => $this->dashboardForExecutor($user),
            'viewer' => $this->dashboardForViewer($baseStats, $olapMetrics),
            default => $this->dashboardForApplicant($user),
        };

        return view('dashboard', array_merge($payload, [
            'role' => $role,
            'user' => $user,
        ]));
    }

    public function notifications()
    {
        $notifications = CaseAction::with(['case', 'user'])
            ->latest()
            ->take(15)
            ->get()
            ->map(function (CaseAction $action) {
                return [
                    'case_id' => $action->case_id,
                    'case_title' => $action->case?->title,
                    'type' => $action->type,
                    'notes' => $action->notes,
                    'performed_by' => $action->user?->name,
                    'at' => $action->created_at,
                ];
            });

        return view('notifications', compact('notifications'));
    }

    public function profile()
    {
        return view('profile');
    }

    protected function loadOlapMetrics(): array
    {
        try {
            $connection = DB::connection(config('olap.connection'));

            $loginRows = $connection->table('fact_user_logins')
                ->selectRaw("date(to_date(date_key, 'YYYYMMDD')) as metric_date, SUM(login_count) as total")
                ->where('date_key', '>=', now()->subDays(30)->format('Ymd'))
                ->groupBy('metric_date')
                ->orderBy('metric_date')
                ->get()
                ->map(fn ($row) => ['date' => $row->metric_date, 'total' => (int) $row->total]);

            $registrationRows = $connection->table('fact_user_registrations')
                ->selectRaw("date(to_date(date_key, 'YYYYMMDD')) as metric_date, COUNT(*) as total")
                ->where('date_key', '>=', now()->subDays(30)->format('Ymd'))
                ->groupBy('metric_date')
                ->orderBy('metric_date')
                ->get()
                ->map(fn ($row) => ['date' => $row->metric_date, 'total' => (int) $row->total]);

            return [
                'enabled' => true,
                'logins' => $loginRows,
                'registrations' => $registrationRows,
            ];
        } catch (\Throwable $e) {
            return [
                'enabled' => false,
                'logins' => collect(),
                'registrations' => collect(),
            ];
        }
    }

    protected function dashboardForAdmin(array $baseStats, array $olap): array
    {
        $statusBreakdown = CaseModel::select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->orderBy('status')
            ->pluck('total', 'status');

        $executorLoad = CaseModel::select('executor_id', DB::raw('COUNT(*) as total'))
            ->whereIn('status', ['new', 'in_progress'])
            ->groupBy('executor_id')
            ->orderByDesc('total')
            ->get()
            ->map(function ($row) {
                $name = optional(User::find($row->executor_id))->name ?? __('Unassigned');
                return ['name' => $name, 'total' => (int) $row->total];
            });

        $trend = CaseModel::selectRaw("to_char(date_trunc('month', created_at), 'YYYY-MM') as label, COUNT(*) as total")
            ->where('created_at', '>=', now()->startOfMonth()->subMonths(11))
            ->groupBy('label')
            ->orderBy('label')
            ->pluck('total', 'label');

        $recentCases = CaseModel::with(['owner', 'executor'])
            ->latest()
            ->take(8)
            ->get();

        return [
            'summaryCards' => [
                ['label' => __('Total cases'), 'value' => number_format($baseStats['totalCases'])],
                ['label' => __('Open cases'), 'value' => number_format($baseStats['openCases'])],
                ['label' => __('Closed cases'), 'value' => number_format($baseStats['closedCases'])],
                ['label' => __('Overdue cases'), 'value' => number_format($baseStats['overdueCases'])],
            ],
            'statusBreakdown' => $statusBreakdown,
            'executorLoad' => $executorLoad,
            'monthlyTrend' => $trend,
            'recentCases' => $recentCases,
            'olap' => $olap,
        ];
    }

    protected function dashboardForExecutor(User $user): array
    {
        $assigned = CaseModel::with('owner')
            ->where('executor_id', $user->id)
            ->orderByDesc('created_at')
            ->take(12)
            ->get();

        $statusSummary = $assigned->groupBy('status')->map->count();

        $upcoming = CaseModel::where('executor_id', $user->id)
            ->whereNotNull('deadline_at')
            ->where('deadline_at', '>=', now())
            ->orderBy('deadline_at')
            ->take(10)
            ->get();

        $activity = CaseAction::with('case')
            ->where('user_id', $user->id)
            ->latest()
            ->take(10)
            ->get();

        return [
            'assignedCases' => $assigned,
            'statusSummary' => $statusSummary,
            'upcomingDeadlines' => $upcoming,
            'recentActivity' => $activity,
        ];
    }

    protected function dashboardForViewer(array $baseStats, array $olap): array
    {
        $latestActions = CaseAction::with(['case', 'user'])
            ->latest()
            ->take(15)
            ->get();

        $topExecutors = CaseModel::select('executor_id', DB::raw('COUNT(*) as total'))
            ->groupBy('executor_id')
            ->orderByDesc('total')
            ->take(5)
            ->get()
            ->map(function ($row) {
                $executor = optional(User::find($row->executor_id));
                return [
                    'name' => $executor?->name ?? __('Unassigned'),
                    'total' => (int) $row->total,
                ];
            });

        return [
            'summaryCards' => [
                ['label' => __('Portfolio size'), 'value' => number_format($baseStats['totalCases'])],
                ['label' => __('Open matters'), 'value' => number_format($baseStats['openCases'])],
                ['label' => __('Closed matters'), 'value' => number_format($baseStats['closedCases'])],
            ],
            'topExecutors' => $topExecutors,
            'latestActions' => $latestActions,
            'olap' => $olap,
        ];
    }

    protected function dashboardForApplicant(User $user): array
    {
        $cases = CaseModel::with('executor')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        $statusCounts = $cases->groupBy('status')->map->count();

        $upcoming = $cases
            ->whereNotNull('deadline_at')
            ->filter(fn (CaseModel $case) => $case->deadline_at->isFuture())
            ->sortBy('deadline_at')
            ->take(10);

        return [
            'myCases' => $cases,
            'statusCounts' => $statusCounts,
            'upcomingDeadlines' => $upcoming,
        ];
    }
}
