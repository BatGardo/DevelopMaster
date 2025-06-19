<!DOCTYPE html>
<html lang="uk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>АСВП Сокіл — Вхід</title>
  <link rel="stylesheet" href="{{ asset('css/testcss.css') }}">
</head>
<body>
  <header class="header">
    <div class="header__logo">
      <!-- Логотип-сокіл у SVG/PNG -->
      <img src="logo.svg" alt="Сокіл">
    </div>
    <h1 class="header__title">АСВП СОКІЛ</h1>
    <a href="/register" class="header__btn">Реєстрація</a>
  </header>

  <main class="main">
    <div class="login">
      <div class="login__tab">Вхід</div>
      <form class="login__form">
        <input type="email" placeholder="email" class="login__input" required>
        <input type="password" placeholder="пароль" class="login__input" required>
        <button type="submit" class="login__submit">Увійти</button>
      </form>
    </div>
  </main>
</body>
</html>
