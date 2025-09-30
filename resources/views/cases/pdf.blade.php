<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>{{ __('Case dossier') }} #{{ $case->id }}</title>
  <style>
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #1f2933; }
    h1, h2 { margin-bottom: 8px; }
    .muted { color: #6b7280; font-size: 11px; }
    .section { margin-bottom: 18px; }
    table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    th, td { border: 1px solid #d1d5db; padding: 6px; text-align: left; }
    th { background: #f3f4f6; }
  </style>
</head>
<body>
  <h1>{{ __('Case dossier') }} #{{ $case->id }}</h1>
  <p class="muted">{{ __('Generated at') }}: {{ $generatedAt->format('Y-m-d H:i') }}</p>

  <div class="section">
    <h2>{{ __('General information') }}</h2>
    <table>
      <tbody>
      <tr><th>{{ __('Title') }}</th><td>{{ $case->title }}</td></tr>
      <tr><th>{{ __('Status') }}</th><td>{{ $case->status_label }}</td></tr>
      <tr><th>{{ __('Owner') }}</th><td>{{ $case->owner?->name ?? __('Unknown') }}</td></tr>
      <tr><th>{{ __('Executor') }}</th><td>{{ $case->executor?->name ?? __('Unassigned') }}</td></tr>
      <tr><th>{{ __('Claimant') }}</th><td>{{ $case->claimant_name ?? '-' }}</td></tr>
      <tr><th>{{ __('Debtor') }}</th><td>{{ $case->debtor_name ?? '-' }}</td></tr>
      <tr><th>{{ __('Deadline') }}</th><td>{{ $case->deadline_at?->format('Y-m-d') ?? '-' }}</td></tr>
      <tr><th>{{ __('Created at') }}</th><td>{{ $case->created_at?->format('Y-m-d H:i') }}</td></tr>
      <tr><th>{{ __('Updated at') }}</th><td>{{ $case->updated_at?->format('Y-m-d H:i') }}</td></tr>
      <tr><th>{{ __('Description') }}</th><td>{!! nl2br(e($case->description)) !!}</td></tr>
      </tbody>
    </table>
  </div>

  <div class="section">
    <h2>{{ __('Timeline') }}</h2>
    <table>
      <thead>
      <tr>
        <th>{{ __('Date') }}</th>
        <th>{{ __('Type') }}</th>
        <th>{{ __('Description') }}</th>
        <th>{{ __('Performed by') }}</th>
      </tr>
      </thead>
      <tbody>
      @forelse ($case->actions as $action)
        <tr>
          <td>{{ $action->created_at->format('Y-m-d H:i') }}</td>
          <td>{{ $action->type }}</td>
          <td>{{ $action->notes ?? __('No comment') }}</td>
          <td>{{ $action->user?->name ?? __('System') }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="4" class="muted">{{ __('No actions recorded yet.') }}</td>
        </tr>
      @endforelse
      </tbody>
    </table>
  </div>

  <div class="section">
    <h2>{{ __('Documents') }}</h2>
    <table>
      <thead>
      <tr>
        <th>{{ __('Title') }}</th>
        <th>{{ __('Uploader') }}</th>
        <th>{{ __('Uploaded at') }}</th>
      </tr>
      </thead>
      <tbody>
      @forelse ($case->documents as $document)
        <tr>
          <td>{{ $document->title }}</td>
          <td>{{ $document->uploader?->name ?? '-' }}</td>
          <td>{{ $document->created_at->format('Y-m-d H:i') }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="3" class="muted">{{ __('No documents attached yet.') }}</td>
        </tr>
      @endforelse
      </tbody>
    </table>
  </div>
</body>
</html>