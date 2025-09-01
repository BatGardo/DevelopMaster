@extends('layouts.app')

@section('content')
<div class="grid md:grid-cols-3 gap-4">
  <div class="bg-white p-4 rounded shadow">
    <p class="text-sm text-slate-500">Користувачів</p>
    <p class="text-2xl font-bold">{{ $stats['users'] }}</p>
  </div>
  <div class="bg-white p-4 rounded shadow">
    <p class="text-sm text-slate-500">Постів</p>
    <p class="text-2xl font-bold">{{ $stats['posts'] }}</p>
  </div>
  <div class="bg-white p-4 rounded shadow">
    <p class="text-sm text-slate-500">Стан системи</p>
    <p class="text-2xl font-bold text-emerald-600">OK</p>
  </div>
</div>
@endsection
