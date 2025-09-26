@extends('layouts.app')

@section('content')
<h2 class="mb-16">Справи</h2>

<div class="card mb-16">
  <form class="grid grid-3" method="GET">
    <div>
      <label class="label">Виконавець</label>
      <select class="input" name="executor">
        <option value="">— будь-хто —</option>
        @foreach($executors as $ex)
          <option value="{{ $ex->id }}" @selected(request('executor')==$ex->id)>{{ $ex->name }}</option>
        @endforeach
      </select>
    </div>
    <div>
      <label class="label">Статус</label>
      <select class="input" name="status">
        <option value="">— будь-який —</option>
        @foreach(['new'=>'Нова','in_progress'=>'В роботі','done'=>'Завершена','closed'=>'Закрита'] as $k=>$v)
          <option value="{{ $k }}" @selected(request('status')==$k)>{{ $v }}</option>
        @endforeach
      </select>
    </div>
    <div style="align-self:end">
      <button class="btn">Застосувати</button>
      <a class="btn btn-primary" href="{{ route('cases.create') }}">+ Нова справа</a>
    </div>
  </form>
</div>

<div class="card">
  <table class="table">
    <thead>
      <tr>
        <th><a href="{{ route('cases.index', array_merge(request()->all(), ['sort'=>'id','direction'=>$sort=='id'&&$direction=='asc'?'desc':'asc'])) }}">ID</a></th>
        <th>Назва</th>
        <th>Виконавець</th>
        <th>Статус</th>
        <th>Строк</th>
        <th>Дії</th>
      </tr>
    </thead>
    <tbody>
      @forelse($cases as $c)
        <tr>
          <td>{{ $c->id }}</td>
          <td><a href="{{ route('cases.show',$c) }}">{{ $c->title }}</a></td>
          <td>{{ $c->executor?->name ?? '—' }}</td>
          <td><span class="badge">{{ $c->status }}</span></td>
          <td>{{ $c->deadline_at?->format('Y-m-d') ?? '—' }}</td>
          <td><a class="btn" href="{{ route('cases.show',$c) }}">Відкрити</a></td>
        </tr>
      @empty
        <tr><td colspan="6">Нічого не знайдено</td></tr>
      @endforelse
    </tbody>
  </table>
  <div class="mt-20">{{ $cases->links() }}</div>
</div>
@endsection
