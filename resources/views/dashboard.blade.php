@extends('layouts.app')

@php use Illuminate\Support\Str; @endphp

@section('content')
  <h2 class="mb-12">{{ __('Welcome back') }}, {{ $user->name }}!</h2>

  @if($role === 'admin')
    <div class="grid grid-4 mb-20" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px;margin-bottom:40px;">
      @foreach($summaryCards as $card)
        <div class="kpi">
          <div class="label">{{ $card['label'] }}</div>
          <div class="value">{{ $card['value'] }}</div>
        </div>
      @endforeach
    </div>

    <div class="grid grid-2 mb-10">
      <div class="card">
        <h3 class="mb-12">{{ __('Cases by status') }}</h3>
        <canvas id="chart-status"></canvas>
      </div>
      <div class="card">
        <h3 class="mb-12">{{ __('Active load per executor') }}</h3>
        <canvas id="chart-executor"></canvas>
      </div>
    </div>

    <div class="grid grid-2 mb-20">
      <div class="card">
        <h3 class="mb-12">{{ __('New cases trend (12 months)') }}</h3>
        <canvas id="chart-trend"></canvas>
      </div>
      <div class="card">
        <h3 class="mb-12">{{ __('Recent cases') }}</h3>
        <table class="table small" style="width:100%;table-layout:fixed;">
          <thead>
          <tr>
            <th style="width:32%;">{{ __('Title') }}</th>
            <th style="width:20%;">{{ __('Region') }}</th>
            <th style="width:22%;">{{ __('Owner') }}</th>
            <th style="width:18%;">{{ __('Executor') }}</th>
            <th style="width:8%;">{{ __('Status') }}</th>
          </tr>
          </thead>
          <tbody>
          @foreach($recentCases as $recent)
            <tr>
              <td style="word-break:break-word;">
                <a href="{{ route('cases.show', $recent) }}">{{ Str::limit($recent->title, 70) }}</a>
              </td>
              <td style="word-break:break-word;">{{ $recent->region_label }}</td>
              <td style="word-break:break-word;">{{ $recent->owner?->name }}</td>
              <td style="word-break:break-word;">{{ $recent->executor?->name ?? __('Unassigned') }}</td>
              <td style="white-space:normal;">
                <span class="badge" style="display:inline-flex;align-items:center;justify-content:center;padding:0 12px;min-width:0;max-width:100%;white-space:normal;">{{ $recent->status_label }}</span>
              </td>
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
          <canvas id="chart-logins"></canvas>
        </div>
        <div class="card">
          <h3 class="mb-12">{{ __('Registrations (30 days)') }}</h3>
          <canvas id="chart-registration"></canvas>
        </div>
      </div>
    @endif
  @elseif($role === 'executor')
    <div class="grid grid-3 mb-20">
      <div class="kpi">
        <div class="label">{{ __('Assigned matters') }}</div>
        <div class="value">{{ $assignedCases->count() }}</div>
      </div>
      <div class="kpi">
        <div class="label">{{ __('Upcoming deadlines < 14d') }}</div>
        <div class="value">{{ $upcomingDeadlines->where('deadline_at', '<=', now()->addDays(14))->count() }}</div>
      </div>
      <div class="kpi">
        <div class="label">{{ __('Recent updates (7)') }}</div>
        <div class="value">{{ min($recentActivity->count(), 7) }}</div>
      </div>
    </div>

    <div class="card mb-20">
      <h3 class="mb-12">{{ __('Status overview') }}</h3>
      <div class="chips">
        @foreach($statusSummary as $status => $count)
          <span class="chip">{{ $statusLabels[$status] ?? $status }} - {{ $count }}</span>
        @endforeach
      </div>
    </div>

    <div class="grid grid-2 mb-20">
      <div class="card">
        <h3 class="mb-12">{{ __('Assigned cases') }}</h3>
        <table class="table small">
          <thead>
          <tr>
            <th>ID</th>
            <th>{{ __('Title') }}</th>
            <th>{{ __('Region') }}</th>
            <th>{{ __('Owner') }}</th>
            <th>{{ __('Deadline') }}</th>
          </tr>
          </thead>
          <tbody>
          @foreach($assignedCases as $case)
            <tr>
              <td>{{ $case->id }}</td>
              <td><a href="{{ route('cases.show', $case) }}">{{ Str::limit($case->title, 40) }}</a></td>
              <td>{{ $case->region_label }}</td>
              <td>{{ $case->owner?->name }}</td>
              <td>{{ $case->deadline_at?->format('Y-m-d') ?? '&mdash;' }}</td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
      <div class="card">
        <h3 class="mb-12">{{ __('Upcoming deadlines') }}</h3>
        <ul class="timeline">
          @forelse($upcomingDeadlines as $case)
            <li>
              <div class="timeline-date">{{ $case->deadline_at?->format('d M') ?? '&mdash;' }}</div>
              <div class="timeline-content">
                <a href="{{ route('cases.show', $case) }}">{{ Str::limit($case->title, 50) }}</a>
                <span class="help">{{ __('Owner') }}: {{ $case->owner?->name ?? '&mdash;' }}</span>
                <span class="help">{{ __('Region') }}: {{ $case->region_label }}</span>
              </div>
            </li>
          @empty
            <li class="help">{{ __('No deadlines scheduled') }}</li>
          @endforelse
        </ul>
      </div>
    </div>

    <div class="card">
      <h3 class="mb-12">{{ __('Recent activity') }}</h3>
      <ul class="timeline">
        @forelse($recentActivity as $activity)
          <li>
            <div class="timeline-date">{{ optional($activity->created_at)->format('d.m H:i') ?? '&mdash;' }}</div>
            <div class="timeline-content">
              <strong>{{ $activity->type_label }}</strong> — {{ $activity->notes ?? __('No comment') }}
              <div class="help">{{ __('Case') }}: <a href="{{ route('cases.show', $activity->case_id) }}">#{{ $activity->case_id }}</a> — {{ __('Region') }}: {{ $activity->case?->region_label ?? __('Not specified') }}</div>
            </div>
          </li>
        @empty
          <li class="help">{{ __('No activity recorded yet') }}</li>
        @endforelse
      </ul>
    </div>
  @elseif($role === 'viewer')
    <div class="grid grid-3 mb-20">
      @foreach($summaryCards as $card)
        <div class="kpi">
          <div class="label">{{ $card['label'] }}</div>
          <div class="value">{{ $card['value'] }}</div>
        </div>
      @endforeach
    </div>

    <div class="grid grid-2 mb-20">
      <div class="card">
        <h3 class="mb-12">{{ __('Top executors') }}</h3>
        <table class="table small">
          <thead>
          <tr>
            <th>{{ __('Executor') }}</th>
            <th>{{ __('Cases') }}</th>
          </tr>
          </thead>
          <tbody>
          @foreach($topExecutors as $row)
            <tr>
              <td>{{ $row['name'] }}</td>
              <td>{{ $row['total'] }}</td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
      <div class="card">
        <h3 class="mb-12">{{ __('Latest actions') }}</h3>
        <ul class="timeline">
          @foreach($latestActions as $action)
            <li>
              <div class="timeline-date">{{ optional($action['at'])->format('d.m H:i') ?? '&mdash;' }}</div>
              <div class="timeline-content">
                <strong>{{ $action['type'] }}</strong> — {{ $action['notes'] ?? __('No comment') }}
                <div class="help">{{ __('Case') }} #{{ $action['case_id'] }} - {{ $action['performed_by'] ?? __('System') }}</div>
                <div class="help">{{ __('Region') }}: {{ $action['case_region'] ?? __('Not specified') }}</div>
              </div>
            </li>
          @endforeach
        </ul>
      </div>
    </div>

    @if($olap['enabled'] ?? false)
      <div class="card mb-20">
        <h3 class="mb-12">{{ __('User logins (30 days)') }}</h3>
        <canvas id="chart-logins"></canvas>
      </div>
    @endif
  @else
    <div class="grid grid-3 mb-20">
      <div class="kpi">
        <div class="label">{{ __('Total cases') }}</div>
        <div class="value">{{ $myCases->count() }}</div>
      </div>
      <div class="kpi">
        <div class="label">{{ __('Active cases') }}</div>
        <div class="value">{{ $statusCounts['in_progress'] ?? 0 }}</div>
      </div>
      <div class="kpi">
        <div class="label">{{ __('Closed cases') }}</div>
        <div class="value">{{ ($statusCounts['done'] ?? 0) + ($statusCounts['closed'] ?? 0) }}</div>
      </div>
    </div>

    <div class="grid grid-2 mb-20">
      <div class="card">
        <h3 class="mb-12">{{ __('My cases') }}</h3>
        <table class="table small">
          <thead>
          <tr>
            <th>ID</th>
            <th>{{ __('Title') }}</th>
            <th>{{ __('Status') }}</th>
            <th>{{ __('Executor') }}</th>
            <th>{{ __('Region') }}</th>
            <th>{{ __('Deadline') }}</th>
          </tr>
          </thead>
          <tbody>
          @foreach($myCases as $case)
            <tr>
              <td>{{ $case->id }}</td>
              <td><a href="{{ route('cases.show', $case) }}">{{ Str::limit($case->title, 50) }}</a></td>
              <td><span class="badge">{{ $case->status_label }}</span></td>
              <td>{{ $case->executor?->name ?? __('Unassigned') }}</td>
              <td>{{ $case->region_label }}</td>
              <td>{{ $case->deadline_at?->format('Y-m-d') ?? '&mdash;' }}</td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
      <div class="card">
        <h3 class="mb-12">{{ __('Upcoming deadlines') }}</h3>
        <ul class="timeline">
          @foreach($upcomingDeadlines as $case)
            <li>
              <div class="timeline-date">{{ $case->deadline_at?->format('d M') ?? '&mdash;' }}</div>
              <div class="timeline-content"><a href="{{ route('cases.show', $case) }}">{{ Str::limit($case->title, 60) }}</a></div>
              <span class="help">{{ __('Region') }}: {{ $case->region_label }}</span>
            </li>
          @endforeach
        </ul>
      </div>
    </div>
  @endif
@endsection

@push('scripts')
  @if($role === 'admin')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
      const statusData = @json($statusBreakdown ?? []);
      const executorLoad = @json(($executorLoad ?? collect())->map(fn($row) => $row)->toArray());
      const monthlyTrend = @json($monthlyTrend ?? []);
      const olap = @json($olap);

      if (Object.keys(statusData).length) {
        new Chart(document.getElementById('chart-status'), {
          type: 'bar',
          data: {
            labels: Object.keys(statusData),
            datasets: [{
              label: '{{ __('Cases') }}',
              data: Object.values(statusData),
              backgroundColor: 'rgba(37, 99, 235, 0.5)',
              borderColor: 'rgba(37, 99, 235, 1)',
              borderWidth: 1
            }]
          },
          options: { responsive: true, scales: { y: { beginAtZero: true } } }
        });
      }

      if (executorLoad.length) {
        new Chart(document.getElementById('chart-executor'), {
          type: 'bar',
          data: {
            labels: executorLoad.map(item => item.name),
            datasets: [{
              label: '{{ __('Open cases') }}',
              data: executorLoad.map(item => item.total),
              backgroundColor: 'rgba(16, 185, 129, 0.5)',
              borderColor: 'rgba(16, 185, 129, 1)',
              borderWidth: 1
            }]
          },
          options: { responsive: true, scales: { y: { beginAtZero: true } } }
        });
      }

      if (Object.keys(monthlyTrend).length) {
        new Chart(document.getElementById('chart-trend'), {
          type: 'line',
          data: {
            labels: Object.keys(monthlyTrend),
            datasets: [{
              label: '{{ __('Created cases') }}',
              data: Object.values(monthlyTrend),
              fill: false,
              tension: 0.2,
              borderColor: 'rgba(59, 130, 246, 1)',
              borderWidth: 2
            }]
          }
        });
      }

      if (olap.enabled) {
        const loginCtx = document.getElementById('chart-logins');
        if (loginCtx) {
          new Chart(loginCtx, {
            type: 'line',
            data: {
              labels: olap.logins.map(row => row.date),
              datasets: [{
                label: '{{ __('Logins') }}',
                data: olap.logins.map(row => row.total),
                borderColor: 'rgba(99, 102, 241, 1)',
                fill: false,
                tension: 0.2,
              }]
            }
          });
        }

        const regCtx = document.getElementById('chart-registration');
        if (regCtx) {
          new Chart(regCtx, {
            type: 'bar',
            data: {
              labels: olap.registrations.map(row => row.date),
              datasets: [{
                label: '{{ __('Registrations') }}',
                data: olap.registrations.map(row => row.total),
                backgroundColor: 'rgba(245, 158, 11, 0.5)',
                borderColor: 'rgba(245, 158, 11, 1)',
                borderWidth: 1
              }]
            }
          });
        }
      }
    </script>
  @elseif($role === 'viewer' && ($olap['enabled'] ?? false))
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
      const viewerOlap = @json($olap);
      if (viewerOlap.enabled) {
        new Chart(document.getElementById('chart-logins'), {
          type: 'line',
          data: {
            labels: viewerOlap.logins.map(row => row.date),
            datasets: [{
              label: '{{ __('Logins') }}',
              data: viewerOlap.logins.map(row => row.total),
              borderColor: 'rgba(99,102,241,1)',
              tension: 0.25,
            }]
          }
        });
      }
    </script>
  @endif
@endpush

