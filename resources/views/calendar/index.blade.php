@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-6xl space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-semibold tracking-widest text-emerald-700">MEALWISE / CALENDAR</p>
            <h1 class="mt-1 text-3xl font-bold text-gray-900">My meal calendar</h1>
            <p class="mt-1 text-sm text-gray-500">Click a meal to see the recipe, store, and full details.</p>
        </div>
        <a href="{{ route('planner.index') }}" class="rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white">Generate new plan</a>
    </div>
    <div id="calendar" class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm"></div>
</div>

<div id="mealModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/50 p-4">
    <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-gray-100 p-5"><h2 id="modalTitle" class="text-xl font-bold text-gray-900"></h2><button id="closeModal" type="button" class="text-2xl text-gray-400">&times;</button></div>
        <div id="modalBody" class="p-5"></div>
    </div>
</div>
@endsection

@push('scripts')
@vite(['resources/css/calendar.css'])
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('mealModal');
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView: 'dayGridMonth', height: 'auto', events: '{{ route('calendar.events') }}',
        eventClick: ({ event }) => {
            const data = event.extendedProps;
            const ingredients = (data.ingredients || []).map(item => `<li>${typeof item === 'string' ? item : `${item.name}: ${item.quantity}`}</li>`).join('');
            const steps = (data.recipe?.steps || []).map(step => `<li>${step}</li>`).join('');
            modal.querySelector('#modalTitle').textContent = event.title;
            modal.querySelector('#modalBody').innerHTML = `<div class="space-y-4"><p class="text-sm font-semibold text-emerald-700">${data.type} · ${event.start.toLocaleDateString('id-ID', { dateStyle: 'full' })}</p>${data.image_url ? `<img src="${data.image_url}" class="h-56 w-full rounded-xl object-cover" alt="${event.title}">` : ''}
            <div class="grid gap-3 sm:grid-cols-2"><div class="rounded-lg bg-gray-50 p-3"><p class="text-xs text-gray-500">Calories</p><p class="font-bold">${data.calories || 0} kcal</p></div><div class="rounded-lg bg-gray-50 p-3"><p class="text-xs text-gray-500">Carbohydrates</p><p class="font-bold">${data.carbs || 0} g</p></div></div>${data.store ? `<div class="rounded-xl border border-emerald-100 bg-emerald-50 p-4"><p class="text-xs font-bold uppercase text-emerald-700">Store</p><p class="mt-1 font-bold">${data.store.name}</p><p class="text-sm text-gray-600">${data.store.address}</p><a target="_blank" href="https://www.google.com/maps/search/?api=1&query=${data.store.latitude},${data.store.longitude}" class="mt-2 inline-block text-xs font-semibold text-emerald-700">Open in Maps →</a></div>` : ''}<div><h3 class="font-bold">Ingredients</h3><ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-gray-600">${ingredients}</ul></div><div><h3 class="font-bold">How to cook</h3><ol class="mt-2 list-decimal space-y-1 pl-5 text-sm text-gray-600">${steps}</ol></div><div class="flex gap-2"><button class="rounded-lg border border-emerald-200 px-3 py-2 text-xs font-semibold text-emerald-700" onclick="moveMeal(${event.id}, '${event.startStr}')">Change date</button><button class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600" onclick="deleteMeal(${event.id})">Delete meal</button></div></div>`;
            modal.classList.remove('hidden'); modal.classList.add('flex');
        }
    });
    calendar.render();
    document.getElementById('closeModal').onclick = () => modal.classList.add('hidden');
    window.moveMeal = async (id, currentDate) => { const date = prompt('New date (YYYY-MM-DD)', currentDate); if (!date) return; await fetch(`/calendar/entries/${id}`, { method: 'PATCH', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify({ date }) }); location.reload(); };
    window.deleteMeal = async (id) => { if (!confirm('Delete this meal?')) return; await fetch(`/calendar/entries/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf } }); location.reload(); };
});
</script>
@endpush
