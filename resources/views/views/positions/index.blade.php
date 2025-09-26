@extends('layouts.app')
@section('content')
<h2 class="mb-16">Позиції</h2>
@if(session('ok')) <div class="alert alert-ok mb-16">{{ session('ok') }}</div> @endif

<div class="card mb-16">
  <a class="btn btn-primary" href="{{ route('positions.create') }}">+ Додати позицію</a>
</div>

<div class="card">
  <table class="table">
    <thead><tr><th>ID</th><th>Назва</th><th>Slug</th><th>Статус</th><th></th></tr></thead>
    <tbody>
      @foreach($positions as $p)
        <tr>
          <td>{{ $p->id }}</td>
          <td>{{ $p->name }}</td>
          <td><span class="badge">{{ $p->slug }}</span></td>
          <td>{!! $p->active ? '<span class="badge">active</span>' : '—' !!}</td>
          <td style="text-align:right">
            <a class="btn" href="{{ route('positions.edit',$p) }}">Редагувати</a>
            <form method="POST" action="{{ route('positions.destroy',$p) }}" style="display:inline">@csrf @method('DELETE')
              <button class="btn btn-danger" onclick="return confirm('Видалити?')">✕</button>
            </form>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
  <div class="mt-20">{{ $positions->links() }}</div>
</div>
@endsection
