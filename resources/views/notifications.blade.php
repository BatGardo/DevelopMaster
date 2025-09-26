@extends('layouts.app')

@section('content')
<h2 class="mb-16">Сповіщення</h2>
<div class="card">
  @forelse($notifications as $n)
    <div class="alert alert-warn mb-8">
      <strong>{{ $n['message'] }}</strong>
      <div class="help">отримано: {{ $n['at']->format('Y-m-d H:i') }}</div>
    </div>
  @empty
    <div class="help">Немає сповіщень</div>
  @endforelse
</div>
@endsection
