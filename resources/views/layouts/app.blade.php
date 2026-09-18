<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meal Planner App</title>
    
    <!-- CSRF Token sangat penting untuk AJAX (FullCalendar Drop) -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Load CSS dan JS bawaan Laravel (Vite) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Tempat untuk memuat CSS/JS spesifik halaman (seperti script FullCalendar) -->
    @stack('scripts')
</head>
<body class="bg-gray-50 text-gray-800 antialiased">

    <!-- Navigation Bar -->
    <nav class="bg-white shadow-sm border-b border-gray-100 px-6 py-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <a href="/" class="text-xl font-bold text-blue-600">PlannerApp</a>
            
            <div class="flex items-center gap-4">
                @guest
                    <!-- Menu jika belum login -->
                    <a href="{{ route('login') }}" class="text-sm font-semibold text-gray-600 hover:text-blue-600">Login</a>
                    <a href="{{ route('register') }}" class="text-sm font-semibold bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Daftar</a>
                @else
                    <!-- Menu jika sudah login -->
                    <a href="{{ route('planner.index') }}" class="text-sm font-semibold text-gray-600 hover:text-blue-600">My Planner</a>
                    <a href="{{ route('calendar.index') }}" class="text-sm font-semibold text-gray-600 hover:text-emerald-700">Calendar</a>
                    <a href="{{ route('recipes.index') }}" class="text-sm font-semibold text-gray-600 hover:text-emerald-700">Recipes</a>
                    <a href="{{ route('membership.index') }}" class="text-sm font-semibold text-gray-600 hover:text-emerald-700">Membership</a>
                    @if(Auth::user()->role === 'admin')
                        <a href="{{ route('admin.dashboard') }}" class="text-sm font-semibold text-emerald-700 hover:text-emerald-800">Dashboard</a>
                        <a href="{{ route('admin.stores.index') }}" class="text-sm font-semibold text-gray-600 hover:text-emerald-700">Stores</a>
                        <a href="{{ route('admin.recipes.index') }}" class="text-sm font-semibold text-gray-600 hover:text-emerald-700">Recipes</a>
                        <a href="{{ route('admin.users.index') }}" class="text-sm font-semibold text-gray-600 hover:text-emerald-700">Users</a>
                        <a href="{{ route('admin.payments.index') }}" class="text-sm font-semibold text-gray-600 hover:text-emerald-700">Payments</a>
                    @endif

                    <span class="text-sm text-gray-500 border-l border-gray-300 pl-4">Halo, {{ Auth::user()->name }}</span>
                    
                    <!-- Tombol Logout -->
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-sm font-semibold text-gray-600 hover:text-red-600">Logout</button>
                    </form>
                @endguest
            </div>
        </div>
    </nav>

    <!-- Konten Halaman Spesifik akan dirender di sini -->
    <main class="max-w-7xl mx-auto p-6">
        @yield('content')
    </main>

</body>
</html>