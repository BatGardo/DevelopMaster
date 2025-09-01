<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;

class HomeController extends Controller
{
    public function index()
    {
        $posts = Post::latest()->take(5)->get();
        return view('welcome', compact('posts'));
    }

    public function dashboard()
    {
        // приклад даних для карток
        $stats = [
            'users' => \App\Models\User::count(),
            'posts' => \App\Models\Post::count(),
        ];
        return view('dashboard', compact('stats'));
    }

    public function cases()
    {
        // заглушка сторінки "Справи"
        return view('cases.index');
    }

    public function analytics()
    {
        // Дані для Chart.js (з БД, простий приклад: пости по місяцях)
        $byMonth = \App\Models\Post::selectRaw("to_char(created_at, 'YYYY-MM') as ym, count(*) as c")
            ->groupBy('ym')->orderBy('ym')->pluck('c','ym');

        $labels = $byMonth->keys()->values();
        $data   = $byMonth->values();

        return view('analytics', compact('labels','data'));
    }

    public function notifications()
    {
        // заглушка — під’єднай пізніше власну таблицю notifications
        $notifications = collect([
            ['message'=>'Сьогодні дедлайн по справі #2025-0012','at'=>now()->subMinutes(10)],
            ['message'=>'Новий коментар у справі #2025-0007','at'=>now()->subHour()],
        ]);
        return view('notifications', compact('notifications'));
    }

    public function profile()
    {
        return view('profile');
    }
}
