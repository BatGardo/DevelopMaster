@extends('layouts.app')

@section('content')
<div class="card" style="max-width:560px;margin:0 auto">
  <h2 class="mb-16 center">Реєстрація</h2>
  <form method="POST" action="{{ route('register') }}">
    @csrf
    <div class="grid grid-2">
      <div class="field">
        <label class="label">Ім’я</label>
        <input class="input" name="name" value="{{ old('name') }}" required>
        @error('name') <div class="help" style="color:var(--danger)">{{ $message }}</div> @enderror
      </div>
      <div class="field">
        <label class="label">Email</label>
        <input class="input" type="email" name="email" value="{{ old('email') }}" required>
        @error('email') <div class="help" style="color:var(--danger)">{{ $message }}</div> @enderror
      </div>
    </div>
    <div class="grid grid-2">
      <div class="field">
        <label class="label">Пароль</label>
        <input class="input" type="password" name="password" required>
      </div>
      <div class="field">
        <label class="label">Підтвердження</label>
        <input class="input" type="password" name="password_confirmation" required>
      </div>
    </div>
    <div class="center">
      <button class="btn btn-primary">Зареєструватися</button>
      <a class="btn" style="margin-left:10px;border:1px solid #cbd5e1" href="{{ route('login.form') }}">Увійти</a>
    </div>
  </form>
</div>
@endsection
