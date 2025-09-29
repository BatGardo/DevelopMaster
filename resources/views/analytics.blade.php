@extends('layouts.app')

@section('content')
  <h2 class="mb-16">{{ __('Analytics dashboard') }}</h2>

  <div class="grid grid-3 mb-20">
    <div class="kpi">
      <div class="label">{{ __('Total cases') }}</div>
      <div class="value">{{ array_sum($byStatus->toArray()) }}</div>
    </div>
    <div class="kpi">
      <div class="label">{{ __('On-time deadlines') }}</div>
      <div class="value">{{ $onTime }}</div>
    </div>
    <div class="kpi">
      <div class="label">{{ __('Overdue matters') }}</div>
      <div class="value">{{ $overdue }}</div>
    </div>
  </div>

  <div class="grid grid-2 mb-20">
    <div class="card">
      <h3 class="mb-12">{{ __('Cases by status') }}</h3>
      <canvas id="analytics-status"></canvas>
    </div>
    <div class="card">
      <h3 class="mb-12">{{ __('Executor workload (top 10)') }}</h3>
      <canvas id="analytics-executors"></canvas>
    </div>
  </div>

  <div class="grid grid-2 mb-20">
    <div class="card">
      <h3 class="mb-12">{{ __('Case registrations trend (12 months)') }}</h3>
      <canvas id="analytics-trend"></canvas>
    </div>
    <div class="card">
      <h3 class="mb-12">{{ __('Top applicants') }}</h3>
      <table class="table small">
        <thead>
        <tr><th>{{ __('Applicant') }}</th><th>{{ __('Cases') }}</th></tr>
        </thead>
        <tbody>
        @foreach($topApplicants as $row)
          <tr>
            <td>{{ $row['name'] }}</td>
            <td>{{ $row['total'] }}</td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>
  </div>

  @if($olap['enabled'])
    <div class="grid grid-2 mb-20">
      <div class="card">
        <h3 class="mb-12">{{ __('User logins (30 days)') }}</h3>
        <canvas id="analytics-logins"></canvas>
      </div>
      <div class="card">
        <h3 class="mb-12">{{ __('Registrations (30 days)') }}</h3>
        <canvas id="analytics-registrations"></canvas>
      </div>
    </div>
  @endif
@endsection

@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    const statusBreakdown = @json($byStatus);
    const executorLoad = @json($executorLoad);
    const trend = @json($trend);
    const olap = @json($olap);

    if (Object.keys(statusBreakdown).length) {
      new Chart(document.getElementById('analytics-status'), {
        type: 'bar',
        data: {
          labels: Object.keys(statusBreakdown),
          datasets: [{
            data: Object.values(statusBreakdown),
            backgroundColor: 'rgba(59,130,246,0.6)',
            borderColor: 'rgba(59,130,246,1)',
            borderWidth: 1,
            label: '{{ __('Cases') }}'
          }]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
      });
    }

    if (executorLoad.length) {
      new Chart(document.getElementById('analytics-executors'), {
        type: 'horizontalBar' in Chart.controllers ? 'horizontalBar' : 'bar',
        data: {
          labels: executorLoad.map(row => row.name),
          datasets: [{
            data: executorLoad.map(row => row.total),
            backgroundColor: 'rgba(16,185,129,0.6)',
            borderColor: 'rgba(16,185,129,1)',
            label: '{{ __('Open cases') }}',
          }]
        },
        options: {
          responsive: true,
          indexAxis: 'y',
          scales: { x: { beginAtZero: true } }
        }
      });
    }

    if (Object.keys(trend).length) {
      new Chart(document.getElementById('analytics-trend'), {
        type: 'line',
        data: {
          labels: Object.keys(trend),
          datasets: [{
            label: '{{ __('Created cases') }}',
            data: Object.values(trend),
            borderColor: 'rgba(99,102,241,1)',
            tension: 0.25,
            fill: false,
          }]
        }
      });
    }

    if (olap.enabled) {
      const loginCtx = document.getElementById('analytics-logins');
      if (loginCtx) {
        new Chart(loginCtx, {
          type: 'line',
          data: {
            labels: olap.logins.map(row => row.date),
            datasets: [{
              label: '{{ __('Logins') }}',
              data: olap.logins.map(row => Number(row.total)),
              borderColor: 'rgba(37,99,235,1)',
              tension: 0.3,
            }]
          }
        });
      }

      const regCtx = document.getElementById('analytics-registrations');
      if (regCtx) {
        new Chart(regCtx, {
          type: 'bar',
          data: {
            labels: olap.registrations.map(row => row.date),
            datasets: [{
              label: '{{ __('Registrations') }}',
              data: olap.registrations.map(row => Number(row.total)),
              backgroundColor: 'rgba(244,114,182,0.6)',
              borderColor: 'rgba(244,114,182,1)',
            }]
          }
        });
      }
    }
  </script>
@endpush
