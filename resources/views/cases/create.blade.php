@extends('layouts.app')
@section('content')
<h2 class="mb-16">Нова справа</h2>
@if(session('ok')) <div class="alert alert-ok mb-16">{{ session('ok') }}</div> @endif

<form class="card" method="POST" action="{{ route('cases.store') }}" enctype="multipart/form-data">
@csrf
<div class="grid grid-2">
  <div class="field">
    <label class="label">Назва</label>
    <input class="input" name="title" required>
  </div>
  <div class="field">
    <label class="label">Виконавець</label>
    <select class="input" name="executor_id">
      <option value="">— не призначено —</option>
      @foreach($executors as $ex) <option value="{{ $ex->id }}">{{ $ex->name }}</option> @endforeach
    </select>
  </div>
  <div class="field">
    <label class="label">Стягувач</label>
    <input class="input" name="claimant_name">
  </div>
  <div class="field">
    <label class="label">Боржник</label>
    <input class="input" name="debtor_name">
  </div>
  <div class="field">
    <label class="label">Строк виконання</label>
    <input class="input" type="date" name="deadline_at">
  </div>
</div>
<div class="field">
  <label class="label">Опис</label>
  <textarea class="input" name="description" rows="4"></textarea>
</div>
<div class="field">
  <label class="label">Документи</label>
  <input class="input" type="file" name="documents[]" multiple>
  <div class="help">PDF/JPG/PNG, до 20МБ кожен</div>
</div>
<div class="center">
  <button class="btn btn-primary">Створити</button>
  <a class="btn" style="border:1px solid #cbd5e1" href="{{ route('cases.index') }}">Скасувати</a>
</div>
</form>
@endsection
