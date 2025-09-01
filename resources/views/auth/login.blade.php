@extends('layouts.app')

@section('content')
<div class="card" style="max-width:520px;margin:0 auto">
  <h2 class="mb-16 center">Вхід до системи</h2>
  <form method="POST" action="{{ route('login') }}">
    @csrf
    <div class="field">
      <label class="label">Email</label>
      <input class="input" type="email" name="email" value="{{ old('email') }}" required>
      @error('email') <div class="help" style="color:var(--danger)">{{ $message }}</div> @enderror
    </div>
    <div class="field">
      <label class="label">Пароль</label>
      <input class="input" type="password" name="password" required>
      @error('password') <div class="help" style="color:var(--danger)">{{ $message }}</div> @enderror
    </div>
    <label class="mb-16" style="display:flex;gap:8px;align-items:center">
      <input type="checkbox" name="remember"> <span class="help">Запам’ятати мене</span>
    </label>
    <div class="center">
      <button class="btn btn-primary">Увійти</button>
      <a class="btn" style="margin-left:10px;border:1px solid #cbd5e1" href="{{ route('register.form') }}">Реєстрація</a>
    </div>
  </form>
</div>
@endsection
