@extends('layouts.app')
@section('content')
@if(session('ok')) <div class="alert alert-ok mb-16">{{ session('ok') }}</div> @endif

<div class="grid grid-2">
  <div class="card">
    <h3 class="mb-16">{{ $case->title }}</h3>
    <p class="mb-8"><b>Статус:</b> <span class="badge">{{ $case->status }}</span></p>
    <p class="mb-8"><b>Стягувач:</b> {{ $case->claimant_name ?? '—' }}</p>
    <p class="mb-8"><b>Боржник:</b> {{ $case->debtor_name ?? '—' }}</p>
    <p class="mb-8"><b>Виконавець:</b> {{ $case->executor?->name ?? '—' }}</p>
    <p class="mb-8"><b>Строк:</b> {{ $case->deadline_at?->format('Y-m-d') ?? '—' }}</p>
    <p class="mb-8"><b>Опис:</b> {!! nl2br(e($case->description)) !!}</p>
  </div>

  <div class="card">
    <h3 class="mb-16">Додати дію</h3>
    <form method="POST" action="{{ route('cases.actions.store',$case) }}">
      @csrf
      <div class="grid grid-2">
        <div class="field">
          <label class="label">Тип</label>
          <select class="input" name="type">
            <option value="custom">Користувацька</option>
            <option value="asset_arrest">Арешт майна</option>
            <option value="notice_sent">Надіслано повідомлення</option>
            <option value="document_added">Додано документ</option>
          </select>
        </div>
        <div class="field">
          <label class="label">Нотатка</label>
          <input class="input" name="notes">
        </div>
      </div>
      <button class="btn">Зберегти</button>
    </form>
    <hr class="mb-16" style="border:none;height:1px;background:#e2e8f0;margin-top:16px">
    <h3 class="mb-16">Завантажити документ</h3>
    <form method="POST" action="{{ route('cases.documents.store',$case) }}" enctype="multipart/form-data">
      @csrf
      <input class="input" type="file" name="file" required>
      <button class="btn">Завантажити</button>
    </form>
  </div>
</div>

<div class="card mt-20">
  <h3 class="mb-16">Хронологія дій</h3>
  @forelse($case->actions as $a)
    <div class="mb-8">
      <div><b>{{ $a->created_at->format('Y-m-d H:i') }}</b> • {{ $a->type }} — {{ $a->notes }}</div>
      <div class="help">виконав: {{ $a->user?->name ?? 'система' }}</div>
    </div>
  @empty
    <div class="help">Ще немає дій</div>
  @endforelse
</div>

<div class="card mt-20">
  <h3 class="mb-16">Документи</h3>
  <ul>
    @forelse($case->documents as $d)
      <li class="mb-8">
        <a href="{{ Storage::disk('public')->url($d->path) }}" target="_blank" class="btn">Завантажити</a>
        {{ $d->title }}
        <span class="help">({{ $d->created_at->format('Y-m-d H:i') }}, {{ $d->uploader?->name ?? '—' }})</span>
      </li>
    @empty
      <li class="help">Документів немає</li>
    @endforelse
  </ul>
</div>
@endsection
