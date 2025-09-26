@extends('layouts.app')
@section('content')
<h2 class="mb-16">Нова позиція</h2>
<form class="card" method="POST" action="{{ route('positions.store') }}">
  @csrf
  <div class="grid grid-2">
    <div class="field"><label class="label">Назва</label><input class="input" name="name" required></div>
    <div class="field"><label class="label">Slug</label><input class="input" name="slug" required></div>
  </div>
  <label class="label">Активна</label>
  <input type="checkbox" name="active" value="1" checked>
  <div class="center mt-20"><button class="btn btn-primary">Зберегти</button></div>
</form>
@endsection
