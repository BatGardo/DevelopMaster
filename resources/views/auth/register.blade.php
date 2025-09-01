@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto bg-white p-6 rounded shadow">
  <h1 class="text-xl font-bold mb-4">Реєстрація</h1>
  <form method="POST" action="{{ route('register') }}">
    @csrf
    <div class="mb-3">
      <label class="block mb-1">Ім’я</label>
      <input type="text" name="name" value="{{ old('name') }}" required class="w-full border rounded px-3 py-2">
      @error('name') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
    </div>
    <div class="mb-3">
      <label class="block mb-1">Email</label>
      <input type="email" name="email" value="{{ old('email') }}" required class="w-full border rounded px-3 py-2">
      @error('email') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
    </div>
    <div class="mb-3">
      <label class="block mb-1">Пароль</label>
      <input type="password" name="password" required class="w-full border rounded px-3 py-2">
    </div>
    <div class="mb-4">
      <label class="block mb-1">Підтвердження пароля</label>
      <input type="password" name="password_confirmation" required class="w-full border rounded px-3 py-2">
      @error('password') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
    </div>
    <div class="flex justify-between items-center">
      <a class="text-slate-600" href="{{ route('login.form') }}">Увійти</a>
      <button class="bg-slate-800 text-white px-4 py-2 rounded">Зареєструватися</button>
    </div>
  </form>
</div>
@endsection

