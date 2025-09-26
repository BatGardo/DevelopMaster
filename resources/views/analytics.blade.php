@extends('layouts.app')

@section('content')
<h2 class="mb-16">Аналітика кейсів</h2>

<div class="grid grid-2">
  <div class="card">
    <h3 class="mb-16">За статусами</h3>
    <canvas id="byStatus"></canvas>
  </div>
  <div class="card">
    <h3 class="mb-16">За виконавцями</h3>
    <canvas id="byExec"></canvas>
  </div>
</div>

<div class="grid grid-2 mt-20">
  <div class="card">
    <h3 class="mb-16">Тренд створення (12 міс.)</h3>
    <canvas id="trend"></canvas>
  </div>
  <div class="card">
    <h3 class="mb-16">Строки</h3>
    <canvas id="deadlines"></canvas>
  </div>
</div>

<div class="grid grid-2 mt-20">
  <div class="kpi">
    <div class="label">Середня тривалість (done), днів</div>
    <div class="value">{{ $avgDurationDays }}</div>
  </div>
  <div class="kpi">
    <div class="label">Ефективність (done / всі)</div>
    <div class="value">{{ $efficiency }}%</div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // Дані з контролера
  const byStatus    = @json($byStatus);             // {new:.., in_progress:.., done:.., closed:..}
  const execLabels  = @json($execLabels);
  const execData    = @json($execData);
  const onTime      = @json($onTime);
  const overdue     = @json($overdue);
  const trendLabels = @json($trendLabels);
  const trendData   = @json($trendData);

  // 1) Статуси
  new Chart(document.getElementById('byStatus'), {
    type: 'bar',
    data: {
      labels: Object.keys(byStatus),
      datasets: [{
        label: 'К-сть',
        data: Object.values(byStatus),
        backgroundColor: 'rgba(29,155,240,.5)',
        borderColor: 'rgba(29,155,240,1)',
        borderWidth: 1
      }]
    },
    options: { responsive: true, scales: { y: { beginAtZero: true } } }
  });

  // 2) Виконавці
  new Chart(document.getElementById('byExec'), {
    type: 'bar',
    data: {
      labels: execLabels,
      datasets: [{
        label: 'Справи',
        data: execData,
        backgroundColor: 'rgba(0,179,164,.4)',
        borderColor: 'rgba(0,179,164,1)',
        borderWidth: 1
      }]
    },
    options: { responsive: true, scales: { y: { beginAtZero: true } } }
  });

  // 3) Тренд (останні 12 місяців)
  new Chart(document.getElementById('trend'), {
    type: 'line',
    data: {
      labels: trendLabels,
      datasets: [{
        label: 'Створено справ',
        data: trendData,
        fill: false,
        borderWidth: 2
      }]
    },
    options: { responsive: true, scales: { y: { beginAtZero: true } } }
  });

  // 4) Строки (в строку / прострочені)
  new Chart(document.getElementById('deadlines'), {
    type: 'doughnut',
    data: {
      labels: ['В строку', 'Прострочені'],
      datasets: [{ data: [onTime, overdue] }]
    }
  });
</script>
@endsection
