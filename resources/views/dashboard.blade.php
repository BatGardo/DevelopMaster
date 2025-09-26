@extends('layouts.app')

@section('content')
<h2 class="mb-16">Панель</h2>
<div class="grid grid-3">
  <div class="kpi">
    <div class="label">Користувачів</div>
    <div class="value">{{ $stats['users'] }}</div>
  </div>
  <div class="kpi">
    <div class="label">Постів</div>
    <div class="value">{{ $stats['posts'] }}</div>
  </div>
  <div class="kpi">
    <div class="label">Стан системи</div>
    <div class="value" style="color:var(--ok)">ОК</div>
  </div>
</div>
@endsection
