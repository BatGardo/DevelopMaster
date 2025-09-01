@extends('layouts.app')

@section('content')
<div class="bg-white p-6 rounded shadow">
  <h1 class="text-xl font-bold mb-4">Аналітика</h1>
  <canvas id="chart" class="max-w-3xl"></canvas>
</div>

<script>
  const labels = @json($labels);
  const data = @json($data);

  new Chart(
    document.getElementById('chart'),
    {
      type: 'bar',
      data: {
        labels,
        datasets: [{
          label: 'Пости за місяцями',
          data,
          backgroundColor: 'rgba(49,170,192,0.5)',
          borderColor: 'rgba(49,170,192,1)',
          borderWidth: 1
        }]
      },
      options: { scales: { y: { beginAtZero: true } } }
    }
  );
</script>
@endsection
