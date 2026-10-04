const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
const steps = ['profile', 'equipment', 'budget', 'results'];
const state = {
    selectedStore: null,
    isSubscribed: false,
    latestPlan: null,
    umkmMenus: [],
    selectedMuscles: new Set(),
    highestStep: 0,
};
const $ = (id) => document.getElementById(id);
const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
}[character]));

function showError(message) {
    $('alert').textContent = message;
    $('alert').hidden = false;
}

function clearError() {
    $('alert').hidden = true;
}

async function request(path, options = {}) {
    const response = await fetch(`/api/${path}`, {
        ...options,
        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken, ...options.headers },
    });
    if (!response.ok) {
        const error = await response.json().catch(() => ({}));
        const details = error.errors ? Object.values(error.errors).flat().join(' ') : error.message;
        throw new Error(details || `Request failed (${response.status})`);
    }
    return response.json();
}

function currentSport() {
    return document.querySelector('input[name="sport"]:checked').value;
}

function goToStep(step) {
    const stepIndex = steps.indexOf(step);
    if (stepIndex < 0 || stepIndex > state.highestStep) {
        return;
    }
    clearError();
    document.querySelectorAll('.wizard-step').forEach((panel) => {
        panel.hidden = panel.dataset.step !== step;
    });
    document.querySelectorAll('.progress-step').forEach((button, index) => {
        button.classList.toggle('active', index === stepIndex);
        button.classList.toggle('complete', index < stepIndex);
        button.disabled = index > state.highestStep;
    });
    history.replaceState(null, '', `#${step}`);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function advanceToStep(step) {
    const nextIndex = steps.indexOf(step);
    state.highestStep = Math.max(state.highestStep, nextIndex);
    goToStep(step);
}

function updateBudgetLabel() {
    const budget = Number($('budget').value);
    $('budgetValue').textContent = Number.isFinite(budget) ? budget.toLocaleString('id-ID') : '0';
}

function setLocation(latitude, longitude, label) {
    $('lat').value = latitude;
    $('lng').value = longitude;
    $('locationStatus').textContent = label;
    $('locationStatus').classList.add('ready');
}

function useLiveLocation() {
    clearError();
    if (!navigator.geolocation) {
        showError('Browser tidak mendukung lokasi langsung. Masukkan koordinat secara manual.');
        return;
    }
    $('locateButton').disabled = true;
    $('locateButton').textContent = 'Mencari lokasi...';
    navigator.geolocation.getCurrentPosition((position) => {
        setLocation(position.coords.latitude.toFixed(6), position.coords.longitude.toFixed(6), 'Lokasi siap');
        $('locateButton').disabled = false;
        $('locateButton').textContent = 'Perbarui lokasi';
    }, () => {
        showError('Akses lokasi ditolak. Izinkan lokasi di browser atau gunakan koordinat manual.');
        $('locateButton').disabled = false;
        $('locateButton').textContent = 'Coba lokasi lagi';
    }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 });
}

function renderStores(stores) {
    $('storeCount').textContent = stores.length
        ? `${stores.length} toko ditemukan di sekitar Anda`
        : 'Tidak ada toko dalam radius 20 km';
    $('storeList').innerHTML = stores.length ? stores.map((store, index) => `
        <button class="store-option ${index === 0 ? 'selected' : ''}" type="button" data-store-id="${escapeHtml(store.id)}">
            <span><b>${escapeHtml(store.name)}</b><small>${escapeHtml(store.address)}</small></span>
            <strong>${Number(store.distance).toFixed(1)} km</strong>
        </button>`).join('') : '<p class="empty-state">Coba lokasi lain untuk menemukan toko terdekat.</p>';
    state.selectedStore = stores[0]?.id ?? null;
    $('storesPanel').hidden = false;
    document.querySelectorAll('.store-option').forEach((option) => option.addEventListener('click', () => {
        document.querySelectorAll('.store-option').forEach((item) => item.classList.remove('selected'));
        option.classList.add('selected');
        state.selectedStore = option.dataset.storeId;
            state.latestPlan = null;
            state.highestStep = Math.min(state.highestStep, 0);
        }));
}

async function findStores() {
    clearError();
    const latitude = Number($('lat').value);
    const longitude = Number($('lng').value);
    if (!Number.isFinite(latitude) || !Number.isFinite(longitude) || latitude < -90 || latitude > 90 || longitude < -180 || longitude > 180) {
        showError('Masukkan koordinat latitude dan longitude yang valid.');
        return;
    }
    $('searchButton').disabled = true;
    $('searchButton').textContent = 'Mencari...';
    try {
        const result = await request('stores/nearest', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ latitude, longitude }),
        });
        renderStores(result.data);
    } catch (error) {
        showError(`Pencarian toko gagal: ${error.message}`);
    } finally {
        $('searchButton').disabled = false;
        $('searchButton').textContent = 'Cari toko terdekat';
    }
}

function renderUmkmMenus(menus, targetId) {
    const target = $(targetId);
    target.innerHTML = menus.length ? menus.map((menu) => `
        <article class="umkm-menu">
            <img src="${escapeHtml(menu.image_url)}" alt="${escapeHtml(menu.name)}" loading="lazy">
            <div class="umkm-menu-content">
                <span class="umkm-seller">UMKM · ${escapeHtml(menu.seller_name)}</span>
                <h4>${escapeHtml(menu.name)}</h4>
                <p>${escapeHtml(menu.description || 'Menu siap santap dari usaha kuliner lokal.')}</p>
                <strong>Rp ${Number(menu.price || 0).toLocaleString('id-ID')} · ${Number(menu.calories || 0).toLocaleString('id-ID')} kcal${menu.carbs === null ? '' : ` · ${Number(menu.carbs)} g karbo`}</strong>
                <details><summary>Lihat menu</summary><p><b>Bahan:</b> ${escapeHtml((menu.ingredients || []).join(', ') || 'Informasi bahan belum tersedia')}</p><p><b>Menu:</b> ${escapeHtml((menu.instructions || []).join(' · ') || 'Silakan hubungi penjual untuk detail menu.')}</p></details>
            </div>
        </article>`).join('') : '<p class="empty-state">Belum ada menu UMKM yang terdaftar untuk cabang olahraga ini. Admin dapat menambahkan menu melalui Recipe Library.</p>';
}

async function loadUmkmMenus() {
    $('umkmList').innerHTML = '<p class="empty-state">Memuat menu UMKM...</p>';
    try {
        const result = await request(`meal-prep/umkm-menus?sport=${encodeURIComponent(currentSport())}`);
        state.umkmMenus = result.data;
        renderUmkmMenus(state.umkmMenus, 'umkmList');
    } catch (error) {
        $('umkmList').innerHTML = '<p class="empty-state">Menu UMKM tidak dapat dimuat.</p>';
        showError(`Menu UMKM gagal dimuat: ${error.message}`);
    }
}

function updateSportView() {
    const isBodybuilding = currentSport() === 'binaraga';
    $('musclePicker').hidden = !isBodybuilding;
    if (!isBodybuilding) {
        state.selectedMuscles.clear();
        updateMuscleSelection();
    }
    loadUmkmMenus();
    $('planPanel').hidden = true;
    state.latestPlan = null;
    state.highestStep = Math.min(state.highestStep, 0);
}

function updateMuscleSelection() {
    const muscles = [...state.selectedMuscles];
    $('selectedMuscles').textContent = muscles.length ? muscles.join(', ') : 'Belum ada area dipilih';
    document.querySelectorAll('[data-muscle]').forEach((zone) => {
        const isSelected = state.selectedMuscles.has(zone.dataset.muscle);
        zone.classList.toggle('selected', isSelected);
        zone.setAttribute('aria-pressed', String(isSelected));
    });
    document.querySelectorAll('[data-muscle-choice]').forEach((choice) => {
        const isSelected = state.selectedMuscles.has(choice.dataset.muscleChoice);
        choice.classList.toggle('selected', isSelected);
        choice.setAttribute('aria-pressed', String(isSelected));
    });
}

function toggleMuscle(muscle) {
    if (state.selectedMuscles.has(muscle)) {
        state.selectedMuscles.delete(muscle);
    } else {
        state.selectedMuscles.add(muscle);
    }
    updateMuscleSelection();
    state.latestPlan = null;
    state.highestStep = Math.min(state.highestStep, 2);
}

function openMuscleModal() {
    $('muscleModal').hidden = false;
    $('closeMuscleModal').focus();
}

function closeMuscleModal() {
    $('muscleModal').hidden = true;
    $('openMuscleModal').focus();
}

function validateProfile() {
    if (!state.selectedStore) {
        showError('Cari dan pilih satu toko bahan terlebih dahulu.');
        return false;
    }
    if (['weight', 'age', 'height'].some((id) => !$(id).value)) {
        showError('Lengkapi berat badan, usia, dan tinggi badan untuk estimasi nutrisi.');
        return false;
    }
    if (currentSport() === 'binaraga' && state.selectedMuscles.size === 0) {
        showError('Pilih minimal satu area otot untuk target binaraga.');
        return false;
    }
    return true;
}

function selectedEquipment() {
    return [...document.querySelectorAll('input[name="equipment"]:checked')].map((input) => input.value);
}

function hasCookingEquipment() {
    return selectedEquipment().some((item) => ['kompor', 'rice_cooker', 'oven', 'air_fryer', 'steamer'].includes(item));
}

function renderPlan(result) {
    state.latestPlan = result;
    $('totalCost').textContent = `Rp ${Number(result.total_cost).toLocaleString('id-ID')}`;
    $('remainingBudget').textContent = `Rp ${Number(result.remaining_budget).toLocaleString('id-ID')}`;
    $('totalCalories').textContent = `${Number(result.daily_calories).toLocaleString('id-ID')} kcal`;
    $('maxCarbs').textContent = `${Number(result.max_carbs_per_day).toLocaleString('id-ID')} g`;
    const sportLabels = { binaraga: 'Binaraga', cycling: 'Cycling', runner: 'Runner' };
    const muscleDescription = result.muscle_groups.length ? ` · Fokus otot: ${result.muscle_groups.join(', ')}` : '';
    $('planType').textContent = `${sportLabels[result.sport]} · target ${Number(result.recommended_calories).toLocaleString('id-ID')} kcal dan ${Number(result.protein_target).toLocaleString('id-ID')} g protein / hari${muscleDescription}`;
    const equipmentLabels = {
        kompor: 'kompor & wajan',
        rice_cooker: 'rice cooker',
        oven: 'oven',
        air_fryer: 'air fryer',
        steamer: 'kukusan',
        blender: 'blender',
        knife: 'pisau & talenan',
        measuring_tools: 'gelas ukur / timbangan',
        food_storage: 'wadah penyimpanan makanan',
    };
    $('equipmentSummary').textContent = `Peralatan yang dipilih: ${result.equipment.map((item) => equipmentLabels[item] || item).join(', ')}.`;
    $('shoppingList').innerHTML = Object.entries(result.shopping_list)
        .flatMap(([category, items]) => items.map((item) => `
            <li class="product-item"><img src="${escapeHtml(item.image_url)}" alt="${escapeHtml(item.name)}" loading="lazy">
                <span><b>${escapeHtml(item.name)}</b><small>${escapeHtml(item.package)} · ${escapeHtml(category)} · Rp ${Number(item.price).toLocaleString('id-ID')}</small></span>
            </li>`)).join('') || '<li>Anggaran belum cukup untuk membeli bahan.</li>';
    $('mealPlan').innerHTML = Object.entries(result.meal_plan).map(([day, meals]) => `
        <div class="meal-day"><strong>${escapeHtml(day)}</strong>
            ${Object.entries(meals).map(([time, detail]) => typeof detail === 'string'
        ? `<p class="meal-warning">${escapeHtml(time)}: ${escapeHtml(detail)}</p>`
        : `<article class="recipe-card"><img src="${escapeHtml(detail.image_url)}" alt="${escapeHtml(detail.recipe.title)}" loading="lazy"><div>
                <span class="recipe-time">${escapeHtml(time)} · ${Number(detail.calories)} kcal · 1 porsi</span>
                <h4>${escapeHtml(detail.recipe.title)}</h4><p>${escapeHtml(detail.menu.join(' · '))}</p>
                <details><summary>Lihat resep</summary><p><b>Bahan (1 porsi):</b></p>
                    <ul class="recipe-ingredients">${detail.recipe.ingredients.map((ingredient) => `<li>${escapeHtml(ingredient.name)}: ${escapeHtml(ingredient.quantity)}</li>`).join('')}</ul>
                    <ol>${detail.recipe.steps.map((step) => `<li>${escapeHtml(step)}</li>`).join('')}</ol>
                </details></div></article>`).join('')}
        </div>`).join('');
    renderUmkmMenus(state.umkmMenus, 'resultUmkmList');
    advanceToStep('results');
}

async function savePlanToCalendar() {
    if (!state.latestPlan) {
        return;
    }
    $('saveCalendarButton').disabled = true;
    const start = new Date(`${state.latestPlan.start_date}T00:00:00`);
    const entries = Object.values(state.latestPlan.meal_plan).flatMap((meals, dayIndex) => Object.entries(meals)
        .filter(([, detail]) => typeof detail !== 'string')
        .map(([type, detail]) => {
            const date = new Date(start);
            date.setDate(date.getDate() + dayIndex);
            return {
                date: `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`,
                type,
                title: detail.recipe.title,
                recipe: detail.recipe,
                ingredients: detail.recipe.ingredients,
                calories: detail.calories,
                carbs: detail.carbs,
                image_url: detail.image_url,
            };
        }));
    try {
        const response = await fetch('/calendar/entries', {
            method: 'POST',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ store_id: state.selectedStore, entries }),
        });
        const result = await response.json().catch(() => ({}));
        if (!response.ok) {
            throw new Error(result.message || `Request failed (${response.status})`);
        }
        $('saveCalendarButton').textContent = 'Tersimpan ke kalender';
    } catch (error) {
        showError(`Penyimpanan kalender gagal: ${error.message}`);
        $('saveCalendarButton').disabled = false;
    }
}

async function generatePlan() {
    clearError();
    const budget = Number($('budget').value);
    if (!state.selectedStore) {
        showError('Pilih toko bahan sebelum membuat plan.');
        advanceToStep('profile');
        return;
    }
    if (!Number.isFinite(budget) || budget < 25000) {
        showError('Anggaran minimum adalah Rp 25.000.');
        return;
    }
    if (!$('startDate').value || (state.isSubscribed && !$('endDate').value)) {
        showError('Pilih tanggal mulai dan tanggal selesai untuk plan membership.');
        return;
    }
    if (!hasCookingEquipment()) {
        showError('Pilih minimal satu alat masak utama yang tersedia di rumah.');
        advanceToStep('equipment');
        return;
    }
    $('generateButton').disabled = true;
    $('generateButton').textContent = 'Menyusun plan...';
    const body = {
        store_id: state.selectedStore,
        budget,
        is_subscribed: state.isSubscribed,
        sport: currentSport(),
        sex: $('sex').value,
        weight: Number($('weight').value),
        age: Number($('age').value),
        height: Number($('height').value),
        muscle_groups: [...state.selectedMuscles],
        equipment: selectedEquipment(),
        start_date: $('startDate').value,
        end_date: $('endDate').value,
    };
    try {
        renderPlan(await request('meal-prep/generate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body),
        }));
    } catch (error) {
        showError(`Plan gagal dibuat: ${error.message}`);
    } finally {
        $('generateButton').disabled = false;
        $('generateButton').textContent = 'Generate meal plan';
    }
}

function todayAsDateInput() {
    const today = new Date();
    return `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
}

document.querySelectorAll('[data-next-step]').forEach((button) => button.addEventListener('click', () => {
    if (button.dataset.nextStep === 'equipment' && !validateProfile()) {
        return;
    }
    if (button.dataset.nextStep === 'budget' && !hasCookingEquipment()) {
        showError('Pilih minimal satu alat masak utama yang tersedia di rumah.');
        return;
    }
    advanceToStep(button.dataset.nextStep);
}));
document.querySelectorAll('[data-prev-step]').forEach((button) => button.addEventListener('click', () => goToStep(button.dataset.prevStep)));
document.querySelectorAll('[data-go-step]').forEach((button) => button.addEventListener('click', () => goToStep(button.dataset.goStep)));
document.querySelectorAll('input[name="sport"]').forEach((input) => input.addEventListener('change', updateSportView));
document.querySelectorAll('[data-muscle], [data-muscle-choice]').forEach((choice) => {
    choice.addEventListener('click', () => toggleMuscle(choice.dataset.muscle || choice.dataset.muscleChoice));
    if (choice.tagName.toLowerCase() === 'g') {
        choice.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                toggleMuscle(choice.dataset.muscle);
            }
        });
    }
});
$('openMuscleModal').addEventListener('click', openMuscleModal);
$('closeMuscleModal').addEventListener('click', closeMuscleModal);
$('saveMuscles').addEventListener('click', closeMuscleModal);
$('muscleModal').addEventListener('click', (event) => {
    if (event.target === $('muscleModal')) {
        closeMuscleModal();
    }
});
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !$('muscleModal').hidden) {
        closeMuscleModal();
    }
});
$('budget').addEventListener('input', () => {
    updateBudgetLabel();
    state.latestPlan = null;
    state.highestStep = Math.min(state.highestStep, 2);
});
$('locateButton').addEventListener('click', useLiveLocation);
$('searchButton').addEventListener('click', findStores);
$('generateButton').addEventListener('click', generatePlan);
$('membershipButton').addEventListener('click', () => { window.location.href = '/membership'; });
$('saveCalendarButton').addEventListener('click', savePlanToCalendar);
document.querySelectorAll('input[name="equipment"]').forEach((input) => input.addEventListener('change', () => {
    state.latestPlan = null;
    state.highestStep = Math.min(state.highestStep, 2);
}));
document.querySelectorAll('#sex, #weight, #age, #height, #startDate, #endDate').forEach((input) => input.addEventListener('change', () => {
    state.latestPlan = null;
    state.highestStep = Math.min(state.highestStep, 2);
}));

const today = todayAsDateInput();
$('startDate').value = today;
$('startDate').min = today;
const subscriptionEnd = new Date(`${today}T00:00:00`);
subscriptionEnd.setDate(subscriptionEnd.getDate() + 6);
$('endDate').value = `${subscriptionEnd.getFullYear()}-${String(subscriptionEnd.getMonth() + 1).padStart(2, '0')}-${String(subscriptionEnd.getDate()).padStart(2, '0')}`;
$('endDate').min = today;
$('startDate').addEventListener('change', () => {
    $('endDate').min = $('startDate').value;
    if (state.isSubscribed && $('endDate').value < $('startDate').value) {
        $('endDate').value = $('startDate').value;
    }
});
updateBudgetLabel();
updateMuscleSelection();
updateSportView();
goToStep('profile');
request('membership').then((result) => {
    state.isSubscribed = result.is_subscribed;
    $('membershipButton').textContent = state.isSubscribed ? 'Membership aktif' : 'Menjadi member';
    $('endDateField').hidden = !state.isSubscribed;
}).catch((error) => showError(`Status membership gagal dimuat: ${error.message}`));
