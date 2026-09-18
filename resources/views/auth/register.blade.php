@extends('layouts.app')

@section('content')
<div class="flex justify-center items-center min-h-screen bg-gray-50">
    <div class="w-full max-w-md bg-white rounded-xl shadow-lg p-8 border border-gray-100">
        <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">Buat Akun Baru</h2>
        <form action="{{ route('register.post') }}" method="POST" class="flex flex-col gap-4">
            @csrf
            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2">Nama Lengkap</label>
                <input type="text" name="name" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-green-500 focus:outline-none" required>
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2">Email</label>
                <input type="email" name="email" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-green-500 focus:outline-none" required>
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2">Password</label>
                <input type="password" name="password" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-green-500 focus:outline-none" required>
            </div>
            <button type="submit" class="w-full bg-green-600 text-white font-bold py-2.5 rounded-lg hover:bg-green-700 transition mt-2">Daftar</button>
        </form>
        <p class="text-center text-sm text-gray-600 mt-6">Sudah punya akun? <a href="{{ route('login') }}" class="text-green-600 font-semibold hover:underline">Masuk</a></p>
    </div>
</div>
@endsection