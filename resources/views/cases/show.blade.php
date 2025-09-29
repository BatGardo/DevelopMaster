@extends('layouts.app')

@section('content')
  @if(session('ok'))
    <div class="alert alert-ok mb-16">{{ session('ok') }}</div>
  @endif

  <div class="grid grid-2">
    <div class="card">
      <h3 class="mb-16">{{ $case->title }}</h3>
      <p class="mb-8"><b>{{ __('Status') }}:</b> <span class="badge">{{ $case->status }}</span></p>
      <p class="mb-8"><b>{{ __('Claimant') }}:</b> {{ $case->claimant_name ?? '—' }}</p>
      <p class="mb-8"><b>{{ __('Debtor') }}:</b> {{ $case->debtor_name ?? '—' }}</p>
      <p class="mb-8"><b>{{ __('Executor') }}:</b> {{ $case->executor?->name ?? __('Unassigned') }}</p>
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
          <div class="timeline-date">{{ $action->created_at->format('d.m H:i') }}</div>
          <div class="timeline-content">
            <strong>{{ $action->type }}</strong> — {{ $action->notes ?? __('No comment') }}
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
    <ul>
      @forelse($case->documents as $document)
        <li class="mb-8">
          <a href="{{ Storage::disk('public')->url($document->path) }}" target="_blank" class="btn">{{ __('Download') }}</a>
          {{ $document->title }}
          <span class="help">({{ $document->created_at->format('Y-m-d H:i') }}, {{ $document->uploader?->name ?? '—' }})</span>
        </li>
      @empty
        <li class="help">{{ __('No documents attached yet.') }}</li>
      @endforelse
    </ul>
  </div>
@endsection
