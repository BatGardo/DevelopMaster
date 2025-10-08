@extends('layouts.app')

@php use Illuminate\Support\Str; @endphp

@section('content')
  @if(session('ok'))
    <div class="alert alert-ok mb-16">{{ session('ok') }}</div>
  @endif

  <div class="grid grid-2">
    <div class="card">
      <div class="flex justify-between items-center mb-12">
        <h3 class="m-0">{{ $case->title }}</h3>
        <a class="btn btn-primary" href="{{ route('cases.export.pdf', $case) }}" target="_blank">{{ __('Export PDF') }}</a>
      </div>
      <p class="mb-8"><b>{{ __('Status') }}:</b> <span class="badge">{{ $case->status_label }}</span></p>
      <p class="mb-8"><b>{{ __('Claimant') }}:</b> {{ $case->claimant_name ?? '—' }}</p>
      <p class="mb-8"><b>{{ __('Debtor') }}:</b> {{ $case->debtor_name ?? '—' }}</p>
      <p class="mb-8"><b>{{ __('Executor') }}:</b> {{ $case->executor?->name ?? __('Unassigned') }}</p>
      <p class="mb-8"><b>{{ __('Region') }}:</b> {{ $case->region_label }}</p>
      <p class="mb-8"><b>{{ __('Deadline') }}:</b> {{ $case->deadline_at?->format('Y-m-d') ?? '—' }}</p>
      <p class="mb-8"><b>{{ __('Description') }}:</b> {!! nl2br(e($case->description)) !!}</p>
    </div>

    <div class="card">
      <h3 class="mb-16">{{ __('Add activity') }}</h3>

      @if($canUpdate)
        <form method="POST" action="{{ route('cases.actions.store', $case) }}" class="mb-16">
          @csrf
          <div class="grid grid-2">
            <div class="field">
              <label class="label">{{ __('Type') }}</label>
              <select class="input" name="type">
                <option value="custom">{{ __('Custom note') }}</option>
                <option value="asset_arrest">{{ __('Asset arrest') }}</option>
                <option value="notice_sent">{{ __('Notice sent') }}</option>
                <option value="document_added">{{ __('Document added') }}</option>
              </select>
            </div>
            <div class="field">
              <label class="label">{{ __('Notes') }}</label>
              <input class="input" name="notes" placeholder="{{ __('Details...') }}">
            </div>
          </div>
          <button class="btn">{{ __('Add action') }}</button>
        </form>

        <h3 class="mb-16">{{ __('Upload document') }}</h3>
        <form method="POST" action="{{ route('cases.documents.store', $case) }}" enctype="multipart/form-data">
          @csrf
          <input class="input mb-8" type="file" name="file" required>
          <button class="btn">{{ __('Upload') }}</button>
        </form>
      @else
        <p class="help">{{ __('You have read-only access to this case.') }}</p>
      @endif
    </div>
  </div>

  <div class="card mt-20">
    <h3 class="mb-16">{{ __('Case timeline') }}</h3>
    <ul class="timeline">
      @forelse($case->actions as $action)
        <li>
          <div class="timeline-date">{{ optional($action->created_at)->format('d.m H:i') ?? '—' }}</div>
          <div class="timeline-content">
            <strong>{{ $action->type_label }}</strong> — {{ $action->notes ?? __('No comment') }}
            <div class="help">{{ __('Performed by') }}: {{ $action->user?->name ?? __('System') }}</div>
          </div>
        </li>
      @empty
        <li class="help">{{ __('No actions recorded yet.') }}</li>
      @endforelse
    </ul>
  </div>

  <div class="card mt-20">
    <h3 class="mb-16">{{ __('Documents') }}</h3>

    @if($case->documents->isEmpty())
      <p class="help">{{ __('No documents attached yet.') }}</p>
    @else
      <table class="table">
        <thead>
        <tr>
          <th>{{ __('Title') }}</th>
          <th>{{ __('Size') }}</th>
          <th>{{ __('Type') }}</th>
          <th>{{ __('Uploaded by') }}</th>
          <th>{{ __('Uploaded at') }}</th>
          <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach($case->documents as $document)
          <tr>
            <td>{{ $document->title }}</td>
            <td>{{ $document->human_size ?? '—' }}</td>
            <td>{{ $document->mime_type ?? __('Unknown') }}</td>
            <td>{{ $document->uploader?->name ?? '—' }}</td>
            <td>{{ $document->created_at?->format('Y-m-d H:i') ?? '—' }}</td>
            <td class="flex gap-2">
              <a class="btn btn-ghost" href="{{ route('cases.documents.download', [$case, $document]) }}">{{ __('Download') }}</a>
              @if($canUpdate)
                <details class="inline-block">
                  <summary class="btn btn-ghost">{{ __('Rename') }}</summary>
                  <form method="POST" action="{{ route('cases.documents.update', [$case, $document]) }}" class="mt-2 space-y-2">
                    @csrf
                    @method('PATCH')
                    <input class="input" name="title" value="{{ $document->title }}" required maxlength="255">
                    <button class="btn btn-primary">{{ __('Save') }}</button>
                  </form>
                </details>
                <form method="POST" action="{{ route('cases.documents.destroy', [$case, $document]) }}" class="inline-block" onsubmit="return confirm('{{ __('Are you sure you want to remove this document?') }}');">
                  @csrf
                  @method('DELETE')
                  <button class="btn btn-ghost">{{ __('Delete') }}</button>
                </form>
              @endif
            </td>
          </tr>
        @endforeach
        </tbody>
      </table>
    @endif
  </div>
@endsection

