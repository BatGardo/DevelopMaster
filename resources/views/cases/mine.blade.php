@extends('layouts.app')
@php use Illuminate\Support\Str; @endphp

@section('content')
  <h2 class="mb-16">{{ __('My cases') }}</h2>

  <div class="card">
    <table class="table">
      <thead>
      <tr>
        <th>ID</th>
        <th>{{ __('Title') }}</th>
        <th>{{ __('Status') }}</th>
        <th>{{ __('Executor') }}</th>
        <th>{{ __('Region') }}</th>
        <th>{{ __('Owner') }}</th>
        <th>{{ __('Deadline') }}</th>
        <th></th>
      </tr>
      </thead>
      <tbody>
      @forelse($cases as $case)
        <tr>
          <td>{{ $case->id }}</td>
          <td>{{ Str::limit($case->title, 60) }}</td>
          <td><span class="badge">{{ $case->status_label }}</span></td>
          <td>{{ $case->executor?->name ?? '-' }}</td>
          <td>{{ $case->region_label }}</td>
          <td>{{ $case->owner?->name ?? '-' }}</td>
          <td>{{ $case->deadline_at?->format('Y-m-d') ?? '-' }}</td>
          <td><a class="btn" href="{{ route('cases.show', $case) }}">{{ __('View') }}</a></td>
        </tr>
      @empty
        <tr><td colspan="8" class="help">{{ __('No cases found for your account.') }}</td></tr>
      @endforelse
      </tbody>
    </table>
    <div class="mt-16">{{ $cases->links() }}</div>
  </div>
@endsection
