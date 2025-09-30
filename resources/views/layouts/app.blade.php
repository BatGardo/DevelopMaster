РїВ»С—<!doctype html>
<html lang="{{ str_replace('_','-',app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>{{ config('app.name','DevelopMaster') }}</title>
  <link rel="icon" href="/favicon.ico">
  <link rel="stylesheet" href="{{ asset('assets/app.css') }}">
  <script defer src="{{ asset('assets/app.js') }}"></script>
</head>
<body>

  <header class="navbar">
    <div class="container wrap">
      <a class="brand" href="{{ route('home') }}">
        <svg viewBox="0 0 24 24" fill="#8bd0ff" xmlns="http://www.w3.org/2000/svg">
          <path d="M4 12c0-5 4-9 9-9 2.8 0 5.3 1.2 7 3.1-2.6-.3-4.7.4-6.3 2.1 1.8-.3 3.2.1 4.4 1.2-2.7.3-4.6 1.6-5.8 3.7 1.2-.4 2.3-.5 3.3-.2-1.6 1.1-3 2.4-3.9 4.2-.8 1.6-2.3 2.8-4.2 3.2C5.7 20.2 4 16.5 4 12z"/>
        </svg>
        <span class="title">ASVP "Sokil"</span>
      </a>

      <nav class="nav-links">
        @auth
          @php($currentUser = auth()->user())
          <a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a>

          @if($currentUser->isAdmin() || $currentUser->isExecutor())
            <a href="{{ route('cases.index') }}">{{ __('Cases') }}</a>
          @endif

          @if($currentUser->isApplicant())
            <a href="{{ route('cases.mine') }}">{{ __('My cases') }}</a>
          @endif

          @if($currentUser->isViewer())
            <a href="{{ route('cases.mine') }}">{{ __('Case monitor') }}</a>
          @endif

          @can('view-analytics')
            <a href="{{ route('analytics.index') }}">{{ __('Analytics overview') }}</a>
            <a href="{{ route('analytics.records') }}">{{ __('Analytics records') }}</a>
          @endcan

          <a href="{{ route('notifications.index') }}">{{ __('Notifications') }}</a>
          <a href="{{ route('profile.index') }}">{{ __('Profile') }}</a>
          <form action="{{ route('logout') }}" method="POST" style="display:inline">@csrf
            <button class="btn btn-ghost">{{ __('Logout') }}</button>
          </form>
        @else
          <a href="{{ route('login.form') }}">{{ __('Login') }}</a>
          <a class="btn btn-primary" href="{{ route('register.form') }}">{{ __('Register') }}</a>
        @endauth
      </nav>
    </div>
  </header>

  <main class="container mt-20">
    @yield('content')
  </main>

  <footer class="footer center">
    &copy; {{ date('Y') }} {{ __('DevelopMaster platform') }}
  </footer>

  @stack('scripts')
</body>
</html>
