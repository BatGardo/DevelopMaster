<div>
    <header class="header">
        <div class="header__logo">
            <img src="{{ asset('logo.svg') }}" alt="Сокіл">
        </div>
        <h1 class="header__title">АСВП СОКІЛ</h1>
        <a href="{{ route('register') }}" class="header__btn">Реєстрація</a>
    </header>

    <main class="main">
        <div class="login">
            <div class="login__tab">Вхід</div>

            <x-auth-session-status class="text-center" :status="session('status')" />

            <form wire:submit="login" class="login__form">
                <input type="email" wire:model="email" placeholder="email" class="login__input" required autofocus autocomplete="email">
                <input type="password" wire:model="password" placeholder="пароль" class="login__input" required autocomplete="current-password">

                <div class="mt-4">
                    <label>
                        <input type="checkbox" wire:model="remember">
                        Запам’ятати мене
                    </label>
                </div>

                <button type="submit" class="login__submit">Увійти</button>
            </form>
        </div>
    </main>
</div>
