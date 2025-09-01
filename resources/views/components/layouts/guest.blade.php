<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>АСВП Сокіл — Вхід</title>

    {{-- Підключення стилю через Vite --}}
    @vite(['resources/css/login.css'])

    {{-- Livewire стилі --}}
    @livewireStyles
</head>
<body>
    {{ $slot }}

    {{-- Livewire скрипти --}}
    @livewireScripts
</body>
</html>
