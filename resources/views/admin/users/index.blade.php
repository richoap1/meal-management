@extends('layouts.app')
@section('content')
<div class="flex flex-col gap-6"><div>
    <p class="text-sm font-semibold text-emerald-700">ADMIN / ACCESS</p>
    <h1 class="text-3xl font-bold text-gray-900">Users</h1>
    <p class="mt-1 text-sm text-gray-500">Manage roles and subscription access.</p>
</div>@if(session('success'))
<div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}
</div>@endif
<div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm"><thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="px-5 py-3">User</th><th class="px-5 py-3">Role</th><th class="px-5 py-3">Membership</th><th class="px-5 py-3 text-right">Action</th></tr></thead><tbody class="divide-y divide-gray-100">@foreach($users as $user)<tr><td class="px-5 py-4"><p class="font-semibold text-gray-900">{{ $user->name }}</p><p class="text-xs text-gray-500">{{ $user->email }}</p></td><td class="px-5 py-4"><span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700">{{ $user->role }}</span></td><td class="px-5 py-4">{{ $user->is_subscribed ? 'Weekly member' : 'Daily plan' }}</td><td class="px-5 py-4 text-right"><a href="{{ route('admin.users.edit', $user) }}" class="text-xs font-semibold text-emerald-700">Edit</a>@if($user->id !== auth()->id())<form class="ml-3 inline" method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete this user?')">@csrf @method('DELETE')<button class="text-xs font-semibold text-red-600">Delete</button></form>@endif</td></tr>@endforeach</tbody></table></div></div></div>
@endsection