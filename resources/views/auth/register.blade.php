<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Регистрация - Take Two Studio</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
        
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col">
        <div class="flex items-center justify-center w-full transition-opacity opacity-100 duration-750 lg:grow">
            <main class="flex max-w-[335px] w-full flex-col lg:max-w-md bg-white dark:bg-[#161615] dark:text-[#EDEDEC] shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d] rounded-lg p-6 lg:p-10">
                <div class="mb-8">
                    <h1 class="text-2xl font-medium mb-1">Регистрация</h1>
                    <p class="text-[#706f6c] dark:text-[#A1A09A]">Създайте своя акаунт, за да започнете.</p>
                </div>

                <form method="POST" action="{{ route('register') }}" class="flex flex-col gap-4">
                    @csrf

                    <!-- Name -->
                    <div class="flex flex-col gap-1.5">
                        <label for="name" class="text-sm font-medium">Име</label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus placeholder="Вашето име"
                            class="px-4 py-2 rounded-sm border border-[#19140035] dark:border-[#3E3E3A] bg-transparent focus:outline-none focus:border-[#f53003] dark:focus:border-[#FF4433] transition-colors">
                        @error('name')
                            <span class="text-xs text-red-500">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Email Address -->
                    <div class="flex flex-col gap-1.5">
                        <label for="email" class="text-sm font-medium">Имейл</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required placeholder="your@email.com"
                            class="px-4 py-2 rounded-sm border border-[#19140035] dark:border-[#3E3E3A] bg-transparent focus:outline-none focus:border-[#f53003] dark:focus:border-[#FF4433] transition-colors">
                        @error('email')
                            <span class="text-xs text-red-500">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div class="flex flex-col gap-1.5">
                        <label for="password" class="text-sm font-medium">Парола</label>
                        <input id="password" type="password" name="password" required placeholder="••••••••"
                            class="px-4 py-2 rounded-sm border border-[#19140035] dark:border-[#3E3E3A] bg-transparent focus:outline-none focus:border-[#f53003] dark:focus:border-[#FF4433] transition-colors">
                        @error('password')
                            <span class="text-xs text-red-500">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Confirm Password -->
                    <div class="flex flex-col gap-1.5">
                        <label for="password_confirmation" class="text-sm font-medium">Потвърди паролата</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required placeholder="••••••••"
                            class="px-4 py-2 rounded-sm border border-[#19140035] dark:border-[#3E3E3A] bg-transparent focus:outline-none focus:border-[#f53003] dark:focus:border-[#FF4433] transition-colors">
                    </div>

                    <div class="mt-4 flex flex-col gap-3">
                        <button type="submit" class="w-full py-2 bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] rounded-sm hover:opacity-90 transition-opacity font-medium">
                            Регистрация
                        </button>
                        
                        <a href="/login" class="text-center text-sm text-[#706f6c] dark:text-[#A1A09A] hover:underline">
                            Вече имате акаунт? Вход
                        </a>
                    </div>
                </form>
            </main>
        </div>
    </body>
</html>
