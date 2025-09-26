<?php

namespace App\Http\Controllers;

use App\Models\CaseModel;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index()
    {

        // 1) розподіл за статусами
        $byStatus = CaseModel::select('status', DB::raw('COUNT(*) as c'))
            ->groupBy('status')
            ->pluck('c', 'status');

        // 2) розподіл за виконавцями (імена)
        $rawByExec = CaseModel::selectRaw('COALESCE(executor_id, 0) as exec, COUNT(*) as c')
            ->groupBy('exec')
            ->pluck('c', 'exec');

        $execLabels = $rawByExec->keys()->map(function ($id) {
            if ((int)$id === 0) return 'Не призначено';
            $u = User::find($id);
            return $u ? $u->name : 'Користувач видалений';
        })->values();

        $execData = $rawByExec->values();

        // 3) строки
        $overdue = CaseModel::whereNotNull('deadline_at')
            ->where('deadline_at', '<', now())
            ->where('status', '!=', 'done')
            ->count();

        $onTime = CaseModel::whereNotNull('deadline_at')
            ->where('deadline_at', '>=', now())
            ->count();

        // 4) тренд за 12 місяців
        $trend = CaseModel::selectRaw("to_char(date_trunc('month', created_at), 'YYYY-MM') as ym, COUNT(*) as c")
            ->where('created_at', '>=', now()->startOfMonth()->subMonths(11))
            ->groupBy('ym')->orderBy('ym')
            ->pluck('c', 'ym');

        $months      = collect(range(11, 0))->map(fn($i) => now()->startOfMonth()->subMonths($i)->format('Y-m'));
        $trendLabels = $months;
        $trendData   = $months->map(fn($ym) => (int)($trend[$ym] ?? 0));

        // 5) ефективність + середня тривалість (на базі updated_at)
        $done  = CaseModel::where('status','done')->count();
        $total = max(1, CaseModel::count());
        $efficiency = round($done / $total * 100, 1);

        $avgDurationDays = (float) CaseModel::where('status','done')
            ->whereNotNull('updated_at')
            ->avg(DB::raw("EXTRACT(EPOCH FROM (updated_at - created_at)) / 86400"));
        $avgDurationDays = round($avgDurationDays, 1);

        // підтримка старих назв (якщо десь лишились)
        $labels = $execLabels;
        $data   = $execData;

        return view('analytics', compact(
            'byStatus',
            'execLabels','execData',
            'onTime','overdue',
            'trendLabels','trendData',
            'avgDurationDays','efficiency',
            'labels','data'
        ));
    }
}
