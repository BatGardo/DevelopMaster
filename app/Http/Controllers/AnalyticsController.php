<?php

namespace App\Http\Controllers;

use App\Models\CaseModel;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index()
    {
        $byStatus = CaseModel::select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->orderBy('status')
            ->pluck('total', 'status');

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
