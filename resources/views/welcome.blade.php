@extends('layouts.app')

@section('content')
<div class="flex flex-col items-center justify-center min-h-[80vh] gap-16">
    
    <!-- Hero Section -->
    <div class="text-center max-w-3xl px-4 mt-12">
        <span class="bg-blue-50 text-blue-600 font-semibold px-4 py-1.5 rounded-full text-sm tracking-wide">
            Aplikasi Perencana Makan #1
        </span>
        <h1 class="text-4xl md:text-6xl font-extrabold text-gray-900 mt-6 leading-tight">
            Rencanakan Menu Makan, <br class="hidden md:block" />
            <span class="text-blue-600">Hemat Waktu & Biaya</span>
        </h1>
        <p class="text-lg text-gray-600 mt-6 mb-8 leading-relaxed">
            Atur jadwal makan mingguan Anda dengan fitur kalender interaktif. Lacak nutrisi, kelola daftar belanja, dan capai target kesehatan Anda dengan lebih mudah.
        </p>
        <div class="flex flex-col sm:flex-row justify-center gap-4">
            <a href="{{ route('register') }}" class="bg-blue-600 text-white font-bold px-8 py-3 rounded-lg shadow-lg hover:bg-blue-700 hover:shadow-xl transition transform hover:-translate-y-0.5">
                Mulai Sekarang — Gratis
            </a>
            <a href="#fitur" class="bg-white text-gray-700 font-semibold px-8 py-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition">
                Pelajari Fitur
            </a>
        </div>
    </div>

    <!-- Features Section -->
    <div id="fitur" class="w-full max-w-6xl pt-12 pb-24 border-t border-gray-100 mt-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-800">Kenapa Menggunakan PlannerApp?</h2>
            <p class="text-gray-500 mt-2">Fasilitas lengkap untuk kebutuhan manajemen dapur Anda.</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 px-4">
            <!-- Fitur 1 -->
            <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition">
                <div class="w-14 h-14 bg-blue-50 rounded-xl flex items-center justify-center text-blue-600 mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-3">Kalender Drag & Drop</h3>
                <p class="text-gray-600 leading-relaxed">
                    Susun jadwal makan mingguan Anda semudah menarik dan menaruh (drag-and-drop) kartu menu makanan ke hari yang diinginkan.
                </p>
            </div>

            <!-- Fitur 2 -->
            <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition">
                <div class="w-14 h-14 bg-green-50 rounded-xl flex items-center justify-center text-green-600 mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-3">Lacak Kalori</h3>
                <p class="text-gray-600 leading-relaxed">
                    Pantau asupan kalori dan nutrisi harian dari setiap resep. Sangat cocok untuk Anda yang sedang menjalankan program diet atau bulking.
                </p>
            </div>

            <!-- Fitur 3 -->
            <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition">
                <div class="w-14 h-14 bg-purple-50 rounded-xl flex items-center justify-center text-purple-600 mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-3">Integrasi Bahan Toko</h3>
                <p class="text-gray-600 leading-relaxed">
                    Daftar belanja Anda terhubung otomatis dengan ketersediaan barang di toko favorit, memastikan Anda tidak kehabisan bahan saat memasak.
                </p>
            </div>
        </div>
    </div>

</div>
@endsection