<!DOCTYPE html>
<html lang="uk">
<head>
  <meta charset="utf-8">
  <title>{{ __('Analytics detail export') }}</title>
  <style>
    body { font-family: Arial, sans-serif; font-size: 12px; }
    h1 { margin-bottom: 8px; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 18px; }
    th, td { border: 1px solid #444; padding: 6px; text-align: left; }
    th { background: #f0f0f0; }
    .section-title { margin-top: 24px; margin-bottom: 8px; font-weight: bold; }
  </style>
</head>
<body>
  <h1>{{ __('Analytics detail export') }}</h1>
  <p>{{ __('Generated at') }}: {{ $generatedAt->format('Y-m-d H:i') }}</p>

  <div class="section">
    <div class="section-title">{{ __('Key metrics') }}</div>
    <table>
      <thead>
      <tr>
        <th>{{ __('Metric') }}</th>
        <th>{{ __('Value') }}</th>
      </tr>
      </thead>
      <tbody>
      @foreach($summary['metrics'] as $metric)
        <tr>
          <td>{{ $metric['label'] }}</td>
          <td>{{ $metric['value'] }}</td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>

  <div class="section">
    <div class="section-title">{{ __('Cases by status') }}</div>
    <table>
      <thead><tr><th>{{ __('Status') }}</th><th>{{ __('Total') }}</th></tr></thead>
      <tbody>
      @foreach($summary['status'] as $row)
        <tr><td>{{ $row['label'] }}</td><td>{{ $row['total'] }}</td></tr>
      @endforeach
      </tbody>
    </table>
  </div>

  <div class="section">
    <div class="section-title">{{ __('Executor workload (top 10)') }}</div>
    <table>
      <thead><tr><th>{{ __('Executor') }}</th><th>{{ __('Cases') }}</th></tr></thead>
      <tbody>
      @foreach($summary['executors'] as $row)
        <tr><td>{{ $row['label'] }}</td><td>{{ $row['total'] }}</td></tr>
      @endforeach
      </tbody>
    </table>
  </div>

  <div class="section">
    <div class="section-title">{{ __('Daily intake') }}</div>
    <table>
      <thead><tr><th>{{ __('Date') }}</th><th>{{ __('Cases created') }}</th></tr></thead>
      <tbody>
      @foreach($summary['daily'] as $row)
        <tr><td>{{ $row['label'] }}</td><td>{{ $row['total'] }}</td></tr>
      @endforeach
      </tbody>
    </table>
  </div>

  <div class="section">
    <div class="section-title">{{ __('Action types') }}</div>
    <table>
      <thead><tr><th>{{ __('Type') }}</th><th>{{ __('Total') }}</th></tr></thead>
      <tbody>
      @foreach($summary['actions'] as $row)
        <tr><td>{{ $row['label'] }}</td><td>{{ $row['total'] }}</td></tr>
      @endforeach
      </tbody>
    </table>
  </div>

  <div class="section">
    <div class="section-title">{{ __('Documents by extension') }}</div>
    <table>
      <thead><tr><th>{{ __('Extension') }}</th><th>{{ __('Total') }}</th></tr></thead>
      <tbody>
      @foreach($summary['documents'] as $row)
        <tr><td>{{ $row['label'] }}</td><td>{{ $row['total'] }}</td></tr>
      @endforeach
      </tbody>
    </table>
  </div>

  <div class="section">
    <div class="section-title">{{ __('Detailed cases') }}</div>
    <table>
      <thead>
      <tr>
        <th>{{ __('ID') }}</th>
        <th>{{ __('Title') }}</th>
        <th>{{ __('Region') }}</th>
        <th>{{ __('Status') }}</th>
        <th>{{ __('Owner') }}</th>
        <th>{{ __('Executor') }}</th>
        <th>{{ __('Created at') }}</th>
        <th>{{ __('Deadline') }}</th>
        <th>{{ __('Actions count') }}</th>
        <th>{{ __('Documents count') }}</th>
        <th>{{ __('Last activity') }}</th>
      </tr>
      </thead>
      <tbody>
      @foreach($cases as $case)
        <tr>
          <td>{{ $case['id'] }}</td>
          <td>{{ $case['title'] }}</td>
          <td>{{ $case['region'] }}</td>
          <td>{{ $case['status'] }}</td>
          <td>{{ $case['owner'] }}</td>
          <td>{{ $case['executor'] }}</td>
          <td>{{ $case['created_at'] }}</td>
          <td>{{ $case['deadline_at'] }}</td>
          <td>{{ $case['actions'] }}</td>
          <td>{{ $case['documents'] }}</td>
          <td>{{ $case['last_activity'] ?? __('N/A') }}</td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
</body>
</html>
