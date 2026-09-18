const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
const state = { selectedStore: null, isSubscribed: false, isDiet: false, latestPlan: null };
const $ = (id) => document.getElementById(id);

function showError(message) { $('alert').textContent = message; $('alert').hidden = false; }
function clearError() { $('alert').hidden = true; }

async function request(path, options = {}) {
    const response = await fetch(`/api/${path}`, { ...options, headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken, ...options.headers } });
    if (!response.ok) {
        const error = await response.json().catch(() => ({}));
        const details = error.errors ? Object.values(error.errors).flat().join(' ') : error.message;
        throw new Error(details || `Request failed (${response.status})`);
    }
    return response.json();
}

function updateBudgetLabel() {
    const budget = Number($('budget').value);
    $('budgetValue').textContent = Number.isFinite(budget) ? budget.toLocaleString('id-ID') : '0';
}

function setMode(isDiet) {
    state.isDiet = isDiet;
    $('dietButton').classList.toggle('active', isDiet); $('nonDietButton').classList.toggle('active', !isDiet); $('dietFields').hidden = !isDiet;
    if (!isDiet) ['weight', 'age', 'height'].forEach((id) => { $(id).value = ''; });
    $('endDateField').hidden = !state.isSubscribed;
}

function setLocation(latitude, longitude, label) { $('lat').value = latitude; $('lng').value = longitude; $('locationStatus').textContent = label; $('locationStatus').classList.add('ready'); }

function useLiveLocation() {
    clearError();
    if (!navigator.geolocation) { showError('Browser tidak mendukung live location. Masukkan koordinat manual.'); return; }
    $('locateButton').disabled = true; $('locateButton').textContent = 'Finding location...';
    navigator.geolocation.getCurrentPosition((position) => { setLocation(position.coords.latitude.toFixed(6), position.coords.longitude.toFixed(6), 'Live location ready'); $('locateButton').disabled = false; $('locateButton').textContent = 'Refresh live location'; }, () => { showError('Akses lokasi ditolak. Izinkan lokasi di browser atau gunakan koordinat manual.'); $('locateButton').disabled = false; $('locateButton').textContent = 'Try live location again'; }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 });
}

function renderStores(stores) {
    $('storeCount').textContent = stores.length ? `${stores.length} store ditemukan di sekitar Anda` : 'Tidak ada store dalam radius 20 km';
    $('storeList').innerHTML = stores.length ? stores.map((store, index) => `<button class="store-option ${index === 0 ? 'selected' : ''}" type="button" data-store-id="${store.id}"><span><b>${store.name}</b><small>${store.address}</small></span><strong>${Number(store.distance).toFixed(1)} km</strong></button>`).join('') : '<p class="empty-state">Coba lokasi lain untuk menemukan supermarket terdekat.</p>';
    state.selectedStore = stores[0]?.id ?? null; $('storesPanel').hidden = false; $('budgetPanel').hidden = !state.selectedStore;
    document.querySelectorAll('.store-option').forEach((option) => option.addEventListener('click', () => { document.querySelectorAll('.store-option').forEach((item) => item.classList.remove('selected')); option.classList.add('selected'); state.selectedStore = option.dataset.storeId; $('budgetPanel').hidden = false; }));
}

async function findStores() {
    clearError(); const latitude = Number($('lat').value); const longitude = Number($('lng').value);
    if (!Number.isFinite(latitude) || !Number.isFinite(longitude) || latitude < -90 || latitude > 90 || longitude < -180 || longitude > 180) { showError('Masukkan koordinat latitude dan longitude yang valid.'); return; }
    $('searchButton').disabled = true; $('searchButton').textContent = 'Searching...';
    try { const result = await request('stores/nearest', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ latitude, longitude }) }); renderStores(result.data); } catch (error) { showError(`Pencarian toko gagal: ${error.message}`); } finally { $('searchButton').disabled = false; $('searchButton').textContent = 'Search nearest stores'; }
}

function renderPlan(result) {
    state.latestPlan = result;
    $('totalCost').textContent = `Rp ${Number(result.total_cost).toLocaleString('id-ID')}`; $('remainingBudget').textContent = `Rp ${Number(result.remaining_budget).toLocaleString('id-ID')}`; $('totalCalories').textContent = `${Number(result.total_calories).toLocaleString('id-ID')} kcal`; $('maxCarbs').textContent = result.max_carbs_per_day ? `${Number(result.max_carbs_per_day).toLocaleString('id-ID')} g` : `${Number(result.daily_carbs).toLocaleString('id-ID')} g`; $('planType').textContent = result.diet ? `${result.subscription_status} • target ${Number(result.recommended_calories).toLocaleString('id-ID')} kcal/day • BMI ${result.bmi}` : `${result.subscription_status} • ${Number(result.daily_calories).toLocaleString('id-ID')} kcal/day`;
    $('shoppingList').innerHTML = Object.entries(result.shopping_list).flatMap(([category, items]) => items.map((item) => `<li class="product-item"><img src="${item.image_url}" alt="${item.name}" loading="lazy"><span><b>${item.name}</b><small>${item.package} · ${category} · Rp ${Number(item.price).toLocaleString('id-ID')}</small></span></li>`)).join('') || '<li>Budget belum cukup untuk membeli bahan.</li>';
    $('mealPlan').innerHTML = Object.entries(result.meal_plan).map(([day, meals]) => `<div class="meal-day"><strong>${day}</strong>${Object.entries(meals).map(([time, detail]) => typeof detail === 'string' ? `<p class="meal-warning">${time}: ${detail}</p>` : `<article class="recipe-card"><img src="${detail.image_url}" alt="${detail.recipe.title}" loading="lazy"><div><span class="recipe-time">${time} · ${detail.calories} kcal · 1 porsi</span><h4>${detail.recipe.title}</h4><p>${detail.menu.join(' · ')}</p><details><summary>View recipe</summary><p><b>Ingredients (1 porsi):</b></p><ul class="recipe-ingredients">${detail.recipe.ingredients.map((ingredient) => `<li>${ingredient.name}: ${ingredient.quantity}</li>`).join('')}</ul><ol>${detail.recipe.steps.map((step) => `<li>${step}</li>`).join('')}</ol></details></div></article>`).join('')}</div>`).join(''); $('planPanel').hidden = false; $('planPanel').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

async function savePlanToCalendar() {
    if (!state.latestPlan) return;
    $('saveCalendarButton').disabled = true;
    const start = new Date(`${state.latestPlan.start_date}T00:00:00`);
    const entries = Object.values(state.latestPlan.meal_plan).flatMap((meals, dayIndex) => Object.entries(meals).filter(([, detail]) => typeof detail !== 'string').map(([type, detail]) => {
        const date = new Date(start); date.setDate(date.getDate() + dayIndex);
        return { date: date.toISOString().slice(0, 10), type, title: detail.recipe.title, recipe: detail.recipe, ingredients: detail.recipe.ingredients, calories: detail.calories, carbs: detail.carbs, image_url: detail.image_url };
    }));
    try {
        const response = await fetch('/calendar/entries', { method: 'POST', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }, body: JSON.stringify({ store_id: state.selectedStore, entries }) });
        const result = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(result.message || `Request failed (${response.status})`);
        $('saveCalendarButton').textContent = 'Saved to calendar';
    } catch (error) { showError(`Calendar save failed: ${error.message}`); $('saveCalendarButton').disabled = false; }
}

async function generatePlan() {
    clearError();
    const budget = Number($('budget').value);
    if (!Number.isFinite(budget) || budget < 25000) { showError('Budget harus minimal Rp 25.000.'); return; }
    if (!$('startDate').value || (state.isSubscribed && !$('endDate').value)) { showError('Pilih tanggal mulai, dan tanggal selesai untuk subscription.'); return; }
    if (state.isDiet && ['weight', 'age', 'height'].some((id) => !$(id).value)) { showError('Lengkapi berat badan, umur, dan tinggi badan untuk mode diet.'); return; }
    $('generateButton').disabled = true; $('generateButton').textContent = 'Building plan...';
    const body = { store_id: state.selectedStore, budget, is_subscribed: state.isSubscribed, is_diet: state.isDiet, start_date: $('startDate').value, end_date: $('endDate').value };
    if (state.isDiet) { body.sex = $('sex').value; body.weight = Number($('weight').value); body.age = Number($('age').value); body.height = Number($('height').value); }
    try { renderPlan(await request('meal-prep/generate', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) })); } catch (error) { showError(`Plan gagal dibuat: ${error.message}`); } finally { $('generateButton').disabled = false; $('generateButton').textContent = 'Generate meal plan'; }
}

async function updateMembership() {
    window.location.href = '/membership';
}

$('budget').addEventListener('input', () => { updateBudgetLabel(); $('planPanel').hidden = true; }); $('nonDietButton').addEventListener('click', () => setMode(false)); $('dietButton').addEventListener('click', () => setMode(true)); $('locateButton').addEventListener('click', useLiveLocation); $('searchButton').addEventListener('click', findStores); $('generateButton').addEventListener('click', generatePlan); $('membershipButton').addEventListener('click', updateMembership); $('saveCalendarButton').addEventListener('click', savePlanToCalendar);
const today = new Date();
const dateValue = today.toISOString().slice(0, 10);
$('startDate').value = dateValue; $('startDate').min = dateValue;
const subscriptionEnd = new Date(today); subscriptionEnd.setDate(subscriptionEnd.getDate() + 6);
$('endDate').value = subscriptionEnd.toISOString().slice(0, 10); $('endDate').min = dateValue;
$('startDate').addEventListener('change', () => { $('endDate').min = $('startDate').value; if (state.isSubscribed && $('endDate').value < $('startDate').value) $('endDate').value = $('startDate').value; });
updateBudgetLabel(); setMode(false);
request('membership').then((result) => { state.isSubscribed = result.is_subscribed; $('membershipButton').textContent = state.isSubscribed ? 'Membership active' : 'Become a member'; $('endDateField').hidden = !state.isSubscribed; }).catch(() => {});