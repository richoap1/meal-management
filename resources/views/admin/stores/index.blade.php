@extends('layouts.app')
@section('content')
<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-4"><div>
        <p class="text-sm font-semibold text-emerald-700">ADMIN / CATALOG</p>
        <h1 class="text-3xl font-bold text-gray-900">Stores</h1>
        <p class="mt-1 text-sm text-gray-500">Manage store locations and their product catalogs.</p>
    </div><a href="{{ route('admin.stores.create') }}" class="rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">+ Add store</a></div>
    @if(session('success'))<div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif
    <div class="grid gap-4 md:grid-cols-2">
        @forelse($stores as $store)
        <article class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"><div class="flex h-40 items-center justify-center bg-gray-100">@if($store->image_path)<img src="{{ asset('storage/'.$store->image_path) }}" alt="{{ $store->name }}" class="h-full w-full object-cover">@else<span class="text-sm text-gray-400">No store image</span>@endif</div><div class="p-5"><div class="flex items-start justify-between gap-3"><div><h2 class="font-bold text-gray-900">{{ $store->name }}</h2><p class="mt-1 text-sm text-gray-500">{{ $store->address }}</p></div><span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">{{ $store->products_count }} products</span></div><p class="mt-3 text-xs text-gray-400">{{ $store->latitude }}, {{ $store->longitude }}</p><div class="mt-5 flex gap-2"><a href="{{ route('admin.stores.products', $store) }}" class="rounded-lg bg-gray-900 px-3 py-2 text-xs font-semibold text-white">Manage products</a><a href="{{ route('admin.stores.edit', $store) }}" class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-700">Edit</a><form method="POST" action="{{ route('admin.stores.destroy', $store) }}" onsubmit="return confirm('Delete this store and its products?')">@csrf @method('DELETE')<button class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600">Delete</button></form></div></div></article>
        @empty<p class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500 md:col-span-2">No stores yet. Add your first store.</p>@endforelse
    </div>
</div>
@endsection