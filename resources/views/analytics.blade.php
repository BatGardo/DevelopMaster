@extends('layouts.app')

@section('content')
  @php
    $normalizeRepeatedLabel = static function (?string $value): ?string {
        if (!is_string($value)) {
            return $value;
        }

        $value = trim($value);
        if ($value === '') {
            return $value;
        }

        if (preg_match('/^(.+?)\1+$/u', $value, $matches)) {
            return $matches[1];
        }

        $encoding = 'UTF-8';
        $length = mb_strlen($value, $encoding);

        for ($i = 1; $i <= $length; $i++) {
            if ($length % $i !== 0) {
                continue;
            }

            $chunk = mb_substr($value, 0, $i, $encoding);
            if ($chunk !== '' && str_repeat($chunk, intdiv($length, $i)) === $value) {
                return $chunk;
            }
        }

        return $value;
    };

    $normalizeSeasonalityRow = static function ($item) use ($normalizeRepeatedLabel): array {
        if ($item instanceof Illuminate\Support\Collection) {
            $item = $item->toArray();
        } elseif (is_object($item)) {
            $item = (array) $item;
        }

        if (!is_array($item)) {
            return [];
        }

        $month = $normalizeRepeatedLabel($item['month'] ?? null);
        $total = $item['total'] ?? ($item['total_cases'] ?? null);
        $avgLead = $item['avgLead'] ?? ($item['avg_lead'] ?? null);

        return [
            'month' => $month,
            'total' => $total !== null ? (int) $total : null,
            'avgLead' => $avgLead !== null ? (float) $avgLead : null,
        ];
    };

    $seasonalitySeriesView = collect($seasonalitySeries ?? [])
        ->map($normalizeSeasonalityRow)
        ->filter(fn (array $row) => ($row['month'] ?? null) !== null)
        ->values()
        ->toArray();

    $seasonalitySummaryView = [
        'average' => round((float) ($seasonalitySummary['average'] ?? 0), 1),
        'peak' => !empty($seasonalitySummary['peak']) ? $normalizeSeasonalityRow($seasonalitySummary['peak']) : null,
        'trough' => !empty($seasonalitySummary['trough']) ? $normalizeSeasonalityRow($seasonalitySummary['trough']) : null,
        'above' => collect($seasonalitySummary['above'] ?? [])
            ->map($normalizeRepeatedLabel)
            ->filter()
            ->unique()
            ->values()
            ->toArray(),
    ];
  @endphp

  <h2 class="mb-16">Аналітична панель</h2>

  <div class="grid grid-3 mb-20" style="margin-bottom: 10px;">
    <div class="kpi">
      <div class="label">Усього справ</div>
      <div class="value">{{ array_sum($byStatus->toArray()) }}</div>
    </div>
    <div class="kpi">
      <div class="label">Дедлайни вчасно</div>
      <div class="value">{{ $onTime }}</div>
    </div>
    <div class="kpi">
      <div class="label">Прострочені справи</div>
      <div class="value">{{ $overdue }}</div>
    </div>
  </div>

  <div class="grid grid-2 mb-20" style="margin-bottom: 10px;">
    <div class="card">
      <h3 class="mb-12">Справи за статусами</h3>
      <canvas id="analytics-status"></canvas>
    </div>
    <div class="card">
      <h3 class="mb-12">Навантаження виконавців (топ 10)</h3>
      <canvas id="analytics-executors"></canvas>
    </div>
  </div>

  <div class="grid grid-2 mb-20" style="margin-bottom: 10px;">
    <div class="card">
      <h3 class="mb-12">Динаміка реєстрацій справ (12 місяців)</h3>
      <canvas id="analytics-trend"></canvas>
    </div>
    <div class="card">
      <h3 class="mb-12">Найактивніші заявники</h3>
      <table class="table small">
        <thead>
        <tr><th>Заявник</th><th>Справи</th></tr>
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

  @if(!empty($seasonalitySeriesView))
    @php
      $seasonalityAverage = $seasonalitySummaryView['average'] ?? 0;
      $seasonalityPeak = $seasonalitySummaryView['peak'] ?? null;
      $seasonalityTrough = $seasonalitySummaryView['trough'] ?? null;
      $seasonalityAbove = $seasonalitySummaryView['above'] ?? [];
    @endphp

    <div class="grid grid-2 mb-20">
      <div class="card">
        <h3 class="mb-12">Огляд сезонності</h3>

        <div class="mb-8">
          <div class="text-sm text-neutral-500">Середня кількість справ на місяць</div>
          <div class="text-xl font-semibold">{{ number_format($seasonalityAverage, 1) }}</div>
        </div>

        @if($seasonalityPeak)
          <div class="mb-6">
            <div class="text-sm text-neutral-500">Піковий місяць</div>
            <div class="text-base font-medium">{{ $seasonalityPeak['month'] ?? 'Не вказано' }}</div>
            <div class="text-sm text-neutral-500">Справи: {{ number_format($seasonalityPeak['total'] ?? 0) }}</div>
          </div>
        @endif

        @if($seasonalityTrough)
          <div class="mb-6">
            <div class="text-sm text-neutral-500">Найтихіший місяць</div>
            <div class="text-base font-medium">{{ $seasonalityTrough['month'] ?? 'Не вказано' }}</div>
            <div class="text-sm text-neutral-500">Справи: {{ number_format($seasonalityTrough['total'] ?? 0) }}</div>
          </div>
        @endif

        @if(!empty($seasonalityAbove))
          <div>
            <div class="text-sm text-neutral-500 mb-2">Місяці вище середнього</div>
            <div class="flex flex-wrap gap-2">
              @foreach($seasonalityAbove as $month)
                <span class="px-2 py-1 rounded bg-neutral-100 text-sm">{{ $month }}</span>
              @endforeach
            </div>
          </div>
        @endif
      </div>

      <div class="card">
        <h3 class="mb-12">Графік сезонності</h3>
        <canvas id="analytics-seasonality"></canvas>
      </div>
    </div>
  @endif

  @if(!empty($regionBreakdown))
    @php
      $regionTop = $regionSummary['top'] ?? null;
      $regionSlow = $regionSummary['slow'] ?? null;
    @endphp

    <div class="grid grid-2 mb-20">
      <div class="card">
        <h3 class="mb-12">Навантаження та швидкість по регіонах</h3>
        <canvas id="analytics-region"></canvas>
      </div>
      <div class="card">
        <h3 class="mb-12">Розподіл за регіонами</h3>

        @if($regionTop)
          <div class="mb-6">
            <div class="text-sm text-neutral-500">Регіон з найбільшою кількістю справ</div>
            <div class="text-base font-medium">{{ $regionTop['region'] ?? 'Не вказано' }}</div>
            <div class="text-sm text-neutral-500">Справи: {{ number_format($regionTop['total'] ?? 0) }}</div>
          </div>
        @endif

        @if($regionSlow)
          <div class="mb-6">
            <div class="text-sm text-neutral-500">Найповільніше вирішення</div>
            <div class="text-base font-medium">{{ $regionSlow['region'] ?? 'Не вказано' }}</div>
            <div class="text-sm text-neutral-500">Середній термін (днів): {{ $regionSlow['avg_lead'] !== null ? number_format($regionSlow['avg_lead'], 1) : 'Н/Д' }}</div>
          </div>
        @endif

        <table class="table small">
          <thead>
          <tr>
            <th>Регіон</th>
            <th>Справи</th>
            <th>Середній термін (днів)</th>
          </tr>
          </thead>
          <tbody>
          @foreach($regionBreakdown as $row)
            <tr>
              <td>{{ $row['region'] ?? 'Не вказано' }}</td>
              <td>{{ number_format($row['total'] ?? 0) }}</td>
              <td>
                @if($row['avg_lead'] !== null)
                  {{ number_format($row['avg_lead'], 1) }}
                @else
                  &mdash;
                @endif
              </td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
    </div>
  @endif

  @if($olap['enabled'])
    <div class="grid grid-2 mb-20">
      <div class="card">
        <h3 class="mb-12">Входи користувачів (30 днів)</h3>
        <canvas id="analytics-logins"></canvas>
      </div>
      <div class="card">
        <h3 class="mb-12">Реєстрації (30 днів)</h3>
        <canvas id="analytics-registrations"></canvas>
      </div>
    </div>
  @endif
@endsection

@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    const statusTotals = @json($statusTotals, JSON_UNESCAPED_UNICODE);
    const statusLabelsMap = @json($statusLabels, JSON_UNESCAPED_UNICODE);
    const statusKeys = Object.keys(statusTotals);
    const statusChartLabels = statusKeys.map(key => statusLabelsMap[key] ?? key);
    const statusChartData = statusKeys.map(key => Number(statusTotals[key]));
    const executorLoad = @json($executorLoad, JSON_UNESCAPED_UNICODE);
    const trend = @json($trend, JSON_UNESCAPED_UNICODE);
    const olap = @json($olap, JSON_UNESCAPED_UNICODE);
    const seasonalitySeries = @json($seasonalitySeriesView, JSON_UNESCAPED_UNICODE);
    const regionBreakdown = @json($regionBreakdown ?? [], JSON_UNESCAPED_UNICODE);

    if (statusKeys.length) {
      new Chart(document.getElementById('analytics-status'), {
        type: 'bar',
        data: {
          labels: statusChartLabels,
          datasets: [{
            data: statusChartData,
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
            label: 'Відкриті справи',
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
            label: 'Створені справи',
            data: Object.values(trend),
            borderColor: 'rgba(99,102,241,1)',
            tension: 0.25,
            fill: false,
          }]
        }
      });
    }

    if (seasonalitySeries.length) {
      const seasonalityCtx = document.getElementById('analytics-seasonality');
      if (seasonalityCtx) {
        new Chart(seasonalityCtx, {
          data: {
            labels: seasonalitySeries.map(row => row.month),
            datasets: [
              {
                type: 'bar',
                label: '{{ __('Cases') }}',
                data: seasonalitySeries.map(row => Number(row.total)),
                backgroundColor: 'rgba(59,130,246,0.6)',
                borderColor: 'rgba(59,130,246,1)',
                borderWidth: 1,
                yAxisID: 'y',
              },
              {
                type: 'line',
                label: 'Середній термін (днів)',
                data: seasonalitySeries.map(row => row.avgLead !== null ? Number(row.avgLead) : null),
                borderColor: 'rgba(249,115,22,1)',
                backgroundColor: 'rgba(249,115,22,0.2)',
                tension: 0.25,
                spanGaps: true,
                yAxisID: 'y1',
              }
            ]
          },
          options: {
            responsive: true,
            scales: {
              y: { beginAtZero: true, position: 'left' },
              y1: {
                beginAtZero: true,
                position: 'right',
                grid: { drawOnChartArea: false }
              }
            }
          }
        });
      }
    }

    if (regionBreakdown.length) {
      const regionCtx = document.getElementById('analytics-region');
      if (regionCtx) {
        new Chart(regionCtx, {
          data: {
            labels: regionBreakdown.map(row => row.region || 'Не вказано'),
            datasets: [
              {
                type: 'bar',
                label: '{{ __('Cases') }}',
                data: regionBreakdown.map(row => Number(row.total)),
                backgroundColor: 'rgba(16,185,129,0.6)',
                borderColor: 'rgba(16,185,129,1)',
                borderWidth: 1,
                yAxisID: 'y',
              },
              {
                type: 'line',
                label: 'Середній термін (днів)',
                data: regionBreakdown.map(row => row.avg_lead !== null ? Number(row.avg_lead) : null),
                borderColor: 'rgba(244,114,182,1)',
                backgroundColor: 'rgba(244,114,182,0.2)',
                tension: 0.25,
                spanGaps: true,
                yAxisID: 'y1',
              }
            ]
          },
          options: {
            responsive: true,
            scales: {
              y: { beginAtZero: true, position: 'left' },
              y1: {
                beginAtZero: true,
                position: 'right',
                grid: { drawOnChartArea: false }
              }
            }
          }
        });
      }
    }

    if (olap.enabled) {
      const loginCtx = document.getElementById('analytics-logins');
      if (loginCtx) {
        new Chart(loginCtx, {
          type: 'line',
          data: {
            labels: olap.logins.map(row => row.date),
            datasets: [{
              label: 'Входи',
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
              label: 'Реєстрації',
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



