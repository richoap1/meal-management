@extends('layouts.app')
@section('content')
<div class="mx-auto max-w-2xl">
    <a href="{{ route('admin.users.index') }}" class="text-sm font-semibold text-emerald-700">← Back to users</a>
    <div class="mt-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <h1 class="text-2xl font-bold text-gray-900">Edit user</h1>
        <form class="mt-6 space-y-5" method="POST" action="{{ route('admin.users.update', $user) }}">
            @csrf
            @method('PUT')
            <label class="block text-sm font-semibold text-gray-700">
                Name
                <input name="name" value="{{ old('name', $user->name) }}" required class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5 font-normal">
            </label>
            <label class="block text-sm font-semibold text-gray-700">
                Email
                <input name="email" type="email" value="{{ old('email', $user->email) }}" required class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5 font-normal">
            </label>
            <div class="grid gap-5 sm:grid-cols-2">
                <label class="block text-sm font-semibold text-gray-700">
                    Role
                    <select name="role" class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5 font-normal">
                        @foreach(['user','member','admin'] as $role)
                            <option {{ old('role', $user->role) === $role ? 'selected' : '' }}>{{ $role }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="flex items-end gap-2 pb-3 text-sm font-semibold text-gray-700">
                    <input name="is_subscribed" type="checkbox" value="1" {{ old('is_subscribed', $user->is_subscribed) ? 'checked' : '' }} class="rounded border-gray-300 text-emerald-700"> Weekly member
                </label>
            </div>
            <label class="block text-sm font-semibold text-gray-700">
                New password <span class="font-normal text-gray-400">(optional)</span>
                <input name="password" type="password" minlength="8" class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5 font-normal">
            </label>
            <button class="w-full rounded-lg bg-emerald-700 px-4 py-3 text-sm font-semibold text-white">Save changes</button>
        </form>
    </div>
</div>
@endsection