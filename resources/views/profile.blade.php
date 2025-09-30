@extends('layouts.app')

@section('content')
<h2 class="mb-16">Профіль</h2>
<div class="card" style="max-width:620px">
  <div class="field">
    <span class="label">Ім’я</span>
    <div class="input" style="border:0;background:#f8fafc">{{ auth()->user()->name }}</div>
  </div>
  <div class="field">
    <span class="label">Email</span>
    <div class="input" style="border:0;background:#f8fafc">{{ auth()->user()->email }}</div>
  </div>
  <div class="alert alert-ok mt-20">Налаштування безпеки й зміна пароля додамо на наступному кроці.</div>
</div>
@endsection
