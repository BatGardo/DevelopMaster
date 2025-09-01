@extends('layouts.app')

@section('content')
<h2 class="mb-16">Справи</h2>
<div class="card">
  <table class="table">
    <thead>
      <tr><th>ID</th><th>Назва</th><th>Опис</th><th>Створено</th></tr>
    </thead>
    <tbody>
      {{-- приклад рядків (замінюється на реальні дані) --}}
      <tr>
        <td>#2025-0001</td>
        <td>Виконавче провадження</td>
        <td><span class="badge">нове</span> Підготовка документів</td>
        <td>2025-09-01</td>
      </tr>
      <tr>
        <td>#2025-0002</td>
        <td>Арешт майна</td>
        <td>Очікує підтвердження</td>
        <td>2025-08-27</td>
      </tr>
    </tbody>
  </table>
</div>
@endsection
