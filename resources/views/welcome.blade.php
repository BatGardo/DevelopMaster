@extends('layouts.app')

@section('content')
<div class="hero card">
  <div>
    <h1>АСВП «Сокіл» — Develop by Столярчук Артем</h1>
    <p class="slogan">Потужно.</p>
    @guest
      <div class="mt-20">
        <a class="btn btn-primary" href="{{ route('login.form') }}">Увійти</a>
        <a class="btn" style="margin-left:10px;border:1px solid #cbd5e1" href="{{ route('register.form') }}">Реєстрація</a>
      </div>
    @endguest
  </div>
  {{-- <div class="card" style="background:#f7fbff">
    <h3 class="mb-16">Останні пости</h3>
    <ul class="mb-0">
      @forelse($posts as $p)
      @empty
        <li class="muted">Немає публікацій</li>
      @endforelse
    </ul>
  </div> --}}
</div>
@endsection
