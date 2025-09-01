@extends('layouts.app')

@section('content')
<div class="bg-white p-6 rounded shadow">
  <h1 class="text-xl font-bold mb-4">Сповіщення</h1>
  <ul class="space-y-2">
    @forelse($notifications as $n)
      <li class="border rounded p-3">
        <div class="font-medium">{{ $n['message'] }}</div>
        <div class="text-xs text-slate-500">{{ $n['at']->format('Y-m-d H:i') }}</div>
      </li>
    @empty
      <li class="text-slate-500">Немає сповіщень</li>
    @endforelse
  </ul>
</div>
@endsection
