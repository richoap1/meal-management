@extends('layouts.app')
@section('content')
<div class="mx-auto max-w-2xl">
    <a href="{{ route('admin.stores.index') }}" class="text-sm font-semibold text-emerald-700">← Back to stores</a>
    <div class="mt-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <h1 class="text-2xl font-bold text-gray-900">{{ $store->exists ? 'Edit store' : 'Add store' }}</h1>
        <p class="mt-1 text-sm text-gray-500">Store image and coordinates help users find the nearest location.</p>
        <form class="mt-6 space-y-5" method="POST" action="{{ $store->exists ? route('admin.stores.update', $store) : route('admin.stores.store') }}" enctype="multipart/form-data">
            @csrf
            @if($store->exists)
                @method('PUT')
            @endif

            @foreach([['name','Store name','text'],['address','Address','text'],['latitude','Latitude','number'],['longitude','Longitude','number']] as [$field,$label,$type])
                <label class="block text-sm font-semibold text-gray-700">
                    {{ $label }}
                    <input name="{{ $field }}" type="{{ $type }}" step="any" value="{{ old($field, $store->$field) }}" required class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2.5 font-normal focus:border-emerald-600 focus:ring-emerald-600">
                    @error($field)
                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </label>
            @endforeach

            <label class="block text-sm font-semibold text-gray-700">
                Store image
                <input name="image" type="file" accept="image/jpeg,image/png,image/webp" class="mt-2 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-normal">
                @error('image')
                    <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                @enderror
            </label>

            <button class="w-full rounded-lg bg-emerald-700 px-4 py-3 text-sm font-semibold text-white hover:bg-emerald-800">
                {{ $store->exists ? 'Save changes' : 'Create store' }}
            </button>
        </form>
    </div>
</div>
@endsection