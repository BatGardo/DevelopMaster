@extends('layouts.app')

@section('content')
  <h2 class="mb-16">{{ __('Activity feed') }}</h2>

  <div class="card">
    @forelse($notifications as $notification)
      <div class="alert alert-warn mb-8">
        <strong>{{ $notification['type'] }}</strong>
        <div>{{ $notification['notes'] ?? __('No description') }}</div>
        <div class="help">
          {{ __('Case') }} #{{ $notification['case_id'] }} В·
          {{ $notification['case_title'] ?? __('Untitled') }} В·
          {{ $notification['performed_by'] ?? __('System') }} В·
          {{ $notification['at']->format('Y-m-d H:i') }}
        </div>
      </div>
    @empty
      <div class="help">{{ __('No activity yet.') }}</div>
    @endforelse
  </div>
@endsection
