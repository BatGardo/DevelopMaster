@extends('layouts.app')

@section('content')
<div class="max-w-lg bg-white p-6 rounded shadow">
  <h1 class="text-xl font-bold mb-4">Профіль</h1>
  <p><b>Ім’я:</b> {{ auth()->user()->name }}</p>
  <p><b>Email:</b> {{ auth()->user()->email }}</p>
  <p class="text-slate-500 mt-3">Тут можна додати форму зміни пароля, 2FA тощо.</p>
</div>
@endsection
