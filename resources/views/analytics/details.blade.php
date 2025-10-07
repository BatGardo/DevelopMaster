@extends('layouts.app')

@section('content')
  <div class="flex justify-between items-center mb-16">
    <h2 class="m-0">{{ __('Analytics detail view') }}</h2>
    <a class="btn btn-primary" href="{{ route('analytics.records.export', request()->query()) }}">{{ __('Export to Excel') }}</a>
  </div>

  <div class="card mb-20">
    <form method="GET" class="grid grid-4 gap-12">
      <div class="field">
        <label class="label" for="status">{{ __('Status') }}</label>
        <select class="input" id="status" name="status">
          <option value="">{{ __('All') }}</option>
          @foreach($filterOptions['statuses'] as $value => $label)
            <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
          @endforeach
        </select>
      </div>
      <div class="field">
        <label class="label" for="executor">{{ __('Executor') }}</label>
        <select class="input" id="executor" name="executor">
          <option value="">{{ __('All') }}</option>
          @foreach($filterOptions['executors'] as $executor)
            <option value="{{ $executor->id }}" @selected((int) $filters['executor'] === $executor->id)>{{ $executor->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="field">
        <label class="label" for="owner">{{ __('Applicant / owner') }}</label>
        <select class="input" id="owner" name="owner">
          <option value="">{{ __('All') }}</option>
          @foreach($filterOptions['owners'] as $owner)
            <option value="{{ $owner->id }}" @selected((int) $filters['owner'] === $owner->id)>{{ $owner->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="field">
        <label class="label">{{ __('Date range') }}</label>
        <div class="grid grid-2">
          <input class="input" type="date" name="date_from" value="{{ $filters['date_from'] }}">
          <input class="input" type="date" name="date_to" value="{{ $filters['date_to'] }}">
        </div>
      </div>
      <div class="field col-span-4 mt-4">
        <button class="btn">{{ __('Apply filters') }}</button>
        <a class="btn btn-ghost" href="{{ route('analytics.records') }}">{{ __('Reset') }}</a>
      </div>
    </form>
  </div>

  <div class="grid grid-4 mb-20">
    <div class="kpi">
      <div class="label">{{ __('Cases in scope') }}</div>
      <div class="value">{{ $totalCases }}</div>
    </div>
    <div class="kpi">
      <div class="label">{{ __('Avg. actions / case') }}</div>
      <div class="value">{{ number_format($avgActionsPerCase, 2) }}</div>
    </div>
    <div class="kpi">
      <div class="label">{{ __('Avg. documents / case') }}</div>
      <div class="value">{{ number_format($avgDocumentsPerCase, 2) }}</div>
    </div>
    <div class="kpi">
      <div class="label">{{ __('Deadline lead (days)') }}</div>
      <div class="value">{{ $deadlineLead ? number_format($deadlineLead, 1) : __('N/A') }}</div>
    </div>
  </div>

  <div class="grid grid-3 mb-20">
    <div class="kpi">
      <div class="label">{{ __('Overdue cases') }}</div>
      <div class="value">{{ $overdueCases }}</div>
    </div>
    <div class="kpi">
      <div class="label">{{ __('On-track cases') }}</div>
      <div class="value">{{ $onTrackCases }}</div>
    </div>
    <div class="kpi">
      <div class="label">{{ __('Action types tracked') }}</div>
      <div class="value">{{ $actionTypes->count() }}</div>
    </div>
  </div>

  <div class="grid grid-2 mb-20">
    <div class="card">
      <h3 class="mb-12">{{ __('Daily intake (filtered)') }}</h3>
      <canvas id="chart-daily"></canvas>
    </div>
    <div class="card">
      <h3 class="mb-12">{{ __('Status distribution') }}</h3>
      <canvas id="chart-status"></canvas>
    </div>
  </div>

  <div class="grid grid-3 mb-20">
    <div class="card">
      <h3 class="mb-12">{{ __('Top executors') }}</h3>
      <table class="table small">
        <thead><tr><th>{{ __('Executor') }}</th><th>{{ __('Cases') }}</th></tr></thead>
        <tbody>
        @forelse($executorSummary as $row)
          <tr><td>{{ $row['executor'] ?? ($row['name'] ?? __('Unassigned')) }}</td><td>{{ $row['total'] }}</td></tr>
        @empty
          <tr><td colspan="2" class="help">{{ __('No data for current filters.') }}</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
    <div class="card">
      <h3 class="mb-12">{{ __('Action breakdown') }}</h3>
      <table class="table small">
        <thead><tr><th>{{ __('Type') }}</th><th>{{ __('Total') }}</th></tr></thead>
        <tbody>
        @forelse($actionTypes as $action)
          <tr><td>{{ $action['type'] }}</td><td>{{ $action['total'] }}</td></tr>
        @empty
          <tr><td colspan="2" class="help">{{ __('No activity recorded.') }}</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
    <div class="card">
      <h3 class="mb-12">{{ __('Documents by extension') }}</h3>
      <table class="table small">
        <thead><tr><th>{{ __('Extension') }}</th><th>{{ __('Total') }}</th></tr></thead>
        <tbody>
        @forelse($documentExtensions as $doc)
          <tr><td>{{ $doc->extension }}</td><td>{{ $doc->total }}</td></tr>
        @empty
          <tr><td colspan="2" class="help">{{ __('No documents uploaded.') }}</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="card mb-20">
    <h3 class="mb-12">{{ __('Detailed case list') }}</h3>
    <div class="table-scroll">
      <table class="table">
        <thead>
        <tr>
          <th>{{ __('Title') }}</th>
          <th>{{ __('Status') }}</th>
          <th>{{ __('Owner') }}</th>
          <th>{{ __('Executor') }}</th>
          <th>{{ __('Created at') }}</th>
          <th>{{ __('Deadline') }}</th>
          <th>{{ __('Actions') }}</th>
          <th>{{ __('Documents') }}</th>
          <th>{{ __('Last activity') }}</th>
          <th></th>
        </tr>
        </thead>
        <tbody>
        @forelse($cases as $case)
          <tr>
            <td>{{ $case->title }}</td>
            <td><span class="badge">{{ $case->status_label }}</span></td>
            <td>{{ $case->owner?->name ?? __('Unknown') }}</td>
            <td>{{ $case->executor?->name ?? __('Unassigned') }}</td>
            <td>{{ $case->created_at?->format('Y-m-d') }}</td>
            <td>{{ $case->deadline_at?->format('Y-m-d') ?? __('N/A') }}</td>
            <td>{{ $case->actions_count }}</td>
            <td>{{ $case->documents_count }}</td>
            <td>{{ $case->actions_max_created_at ? \Illuminate\Support\Carbon::parse($case->actions_max_created_at)->format('Y-m-d H:i') : __('N/A') }}</td>
            <td><a class="btn btn-ghost" href="{{ route('cases.show', $case) }}">{{ __('Open') }}</a></td>
          </tr>
        @empty
          <tr><td colspan="10" class="help">{{ __('No cases match the current filters.') }}</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
    <div class="mt-12">{{ $cases->links() }}</div>
  </div>

  @if($olap['enabled'])
    <div class="card mb-20">
      <h3 class="mb-12">{{ __('OLAP snapshot (30 days)') }}</h3>
      <div class="grid grid-2">
        <div>
          <h4 class="mb-8">{{ __('Logins') }}</h4>
          <canvas id="chart-olap-logins"></canvas>
        </div>
        <div>
          <h4 class="mb-8">{{ __('Registrations') }}</h4>
          <canvas id="chart-olap-registrations"></canvas>
        </div>
      </div>
    </div>
  @endif
@endsection

@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    const dailySeries = @json($dailySeries);
    const statusSummary = @json($statusSummary);
    const olap = @json($olap);

    if (dailySeries.length) {
      new Chart(document.getElementById('chart-daily'), {
        type: 'line',
        data: {
          labels: dailySeries.map(item => item.day),
          datasets: [{
            label: '{{ __('Cases created') }}',
            data: dailySeries.map(item => item.total),
            borderColor: 'rgba(37,99,235,1)',
            fill: false,
            tension: 0.3,
          }]
        }
      });
    }

    if (statusSummary.length) {
      new Chart(document.getElementById('chart-status'), {
        type: 'bar',
        data: {
          labels: statusSummary.map(item => item.status),
          datasets: [{
            label: '{{ __('Cases') }}',
            data: statusSummary.map(item => item.total),
            backgroundColor: 'rgba(16,185,129,0.6)',
            borderColor: 'rgba(16,185,129,1)'
          }]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
      });
    }

    if (olap.enabled) {
      const loginsCtx = document.getElementById('chart-olap-logins');
      if (loginsCtx) {
        new Chart(loginsCtx, {
          type: 'line',
          data: {
            labels: olap.logins.map(row => row.metric_date ?? row.date),
            datasets: [{
              label: '{{ __('Logins') }}',
              data: olap.logins.map(row => Number(row.total)),
              borderColor: 'rgba(99,102,241,1)',
              tension: 0.25,
              fill: false,
            }]
          }
        });
      }

      const regCtx = document.getElementById('chart-olap-registrations');
      if (regCtx) {
        new Chart(regCtx, {
          type: 'bar',
          data: {
            labels: olap.registrations.map(row => row.metric_date ?? row.date),
            datasets: [{
              label: '{{ __('Registrations') }}',
              data: olap.registrations.map(row => Number(row.total)),
              backgroundColor: 'rgba(244,114,182,0.7)',
            }]
          }
        });
      }
    }
  </script>
@endpush






