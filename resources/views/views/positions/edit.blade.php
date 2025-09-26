@extends('layouts.app')
@section('content')
<h2 class="mb-16">Редагувати позицію</h2>
<form class="card" method="POST" action="{{ route('positions.update',$position) }}">
  @csrf @method('PUT')
  <div class="grid grid-2">
    <div class="field"><label class="label">Назва</label>
      <input class="input" name="name" value="{{ $position->name }}" required>
    </div>
    <div class="field"><label class="label">Slug</label>
      <input class="input" name="slug" value="{{ $position->slug }}" required>
    </div>
  </div>
  <label class="label">Активна</label>
  <input type="checkbox" name="active" value="1" {{ $position->active ? 'checked' : '' }}>
  <div class="center mt-20"><button class="btn btn-primary">Оновити</button></div>
</form>
@endsection
