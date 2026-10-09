@extends ('layouts.app')

@section ('content')
    <div class="meal-page">
        <section class="meal-hero">
            <div>
                <p class="eyebrow">SMART MEAL PLANNER</p>
                <h1>Plan meals that fit <span>your training.</span></h1>
                <p class="hero-copy">Pilih program, perlengkapan dapur, dan anggaran untuk menyusun menu serta menemukan UMKM kuliner yang sesuai.</p>
            </div>
            <div class="hero-badge"><span>●</span> Nutrisi sesuai cabang olahraga</div>
        </section>

        <div class="planner-progress" aria-label="Tahapan perencanaan">
            <button class="progress-step active" type="button" data-go-step="profile">
                <span>01</span> Profil & penyedia
            </button>
            <span aria-hidden="true">→</span>
            <button class="progress-step" type="button" data-go-step="equipment">
                <span>02</span> Peralatan
            </button>
            <span aria-hidden="true">→</span>
            <button class="progress-step" type="button" data-go-step="budget">
                <span>03</span> Anggaran
            </button>
            <span aria-hidden="true">→</span>
            <button class="progress-step" type="button" data-go-step="results">
                <span>04</span> Hasil plan
            </button>
        </div>

        <div id="alert" class="planner-alert" role="alert" hidden></div>

        <section class="planner-card wizard-step" data-step="profile">
            <div class="card-title">
                <span class="step">01</span>
                <div>
                    <h2>Profil latihan & penyedia makanan</h2>
                    <p>Target kalori dan karbohidrat mengikuti kebutuhan cabang olahraga Anda.</p>
                </div>
            </div>

            <fieldset class="sport-options">
                <legend>Pilih olahraga atau program makan</legend>
                <label class="sport-option"
                    ><input type="radio" name="sport" value="binaraga" checked /><span
                        class="sport-icon"
                        aria-hidden="true"
                        >🏋</span
                    ><span
                        ><b>Binaraga</b
                        ><small>Dukungan energi dan protein untuk latihan beban.</small></span
                    ></label
                >
                <label class="sport-option"
                    ><input type="radio" name="sport" value="cycling" /><span
                        class="sport-icon"
                        aria-hidden="true"
                        >🚴</span
                    ><span
                        ><b>Cycling</b
                        ><small>Fokus karbohidrat untuk aktivitas bersepeda.</small></span
                    ></label
                >
                <label class="sport-option"
                    ><input type="radio" name="sport" value="runner" /><span
                        class="sport-icon"
                        aria-hidden="true"
                        >🏃</span
                    ><span
                        ><b>Runner</b><small>Energi seimbang untuk lari dan pemulihan.</small></span
                    ></label
                >
                <label class="sport-option"
                    ><input type="radio" name="sport" value="normal" /><span
                        class="sport-icon"
                        aria-hidden="true"
                        >🌿</span
                    ><span
                        ><b>Aktivitas normal</b
                        ><small>Tidak mengikuti olahraga atau program makan khusus.</small></span
                    ></label
                >
            </fieldset>

            <div id="musclePicker" class="muscle-picker" hidden>
                <div>
                    <h3>Target pembentukan otot</h3>
                    <p>Pilih area otot yang ingin lebih signifikan dibentuk.</p>
                </div>
                <button id="openMuscleModal" class="planner-button secondary" type="button">
                    Pilih bagian otot
                </button>
                <span id="selectedMuscles" class="selection-note">Belum ada area dipilih</span>
            </div>

            <div class="profile-fields">
                <label
                    >Jenis kelamin untuk estimasi metabolisme<select id="sex">
                        <option value="female">Perempuan</option>
                        <option value="male">Laki-laki</option>
                    </select></label
                >
                <label
                    >Berat badan (kg)<input
                        id="weight"
                        type="number"
                        min="20"
                        max="300"
                        step=".1"
                        value="60"
                        required
                /></label>
                <label
                    >Usia (tahun; desimal untuk remaja)<input
                        id="age"
                        type="number"
                        min="13"
                        max="100"
                        step=".1"
                        value="25"
                        required
                /></label>
                <label
                    >Tinggi badan (cm)<input
                        id="height"
                        type="number"
                        min="100"
                        max="230"
                        step=".1"
                        value="165"
                        required
                /></label>
            </div>
            <label class="planner-field"
                >Makanan atau bahan yang tidak bisa dikonsumsi
                <textarea
                    id="excludedFoods"
                    rows="3"
                    maxlength="1000"
                    placeholder="Contoh: udang, susu, kacang tanah"
                ></textarea
                ><small
                    >Pisahkan dengan koma atau baris baru. Bahan yang cocok akan dikeluarkan dari
                    daftar belanja dan resep. Untuk alergi serius, tetap periksa label produk dan
                    risiko kontaminasi silang.</small
                >
            </label>
            <!-- prettier-ignore -->
            <p class="field-hint">
                BMI digunakan sebagai skrining umum, bukan diagnosis. Usia 13–18 memakai kurva
                BMI-menurut-umur WHO 2007; usia 19+ memakai ambang BMI dewasa. Binaraga
                dikecualikan dari otomasi BMI. Diet penurunan berat untuk dewasa memakai defisit
                ringan; remaja tidak diberi target defisit kalori. Konsultasikan tenaga kesehatan
                untuk kebutuhan khusus.
            </p>

            <article class="planner-card location-card">
                <div class="card-title">
                    <span class="step">A</span>
                    <div>
                        <h3>Supermarket & toko bahan</h3>
                        <p>Pilih toko untuk daftar belanja plan Anda.</p>
                    </div>
                </div>
                <div class="location-actions">
                    <button id="locateButton" class="planner-button primary" type="button">
                        Gunakan lokasi saat ini</button
                    ><span id="locationStatus" class="location-status">Lokasi belum diatur</span>
                </div>
                <!-- prettier-ignore -->
                <p class="field-hint">
                    Produk mengikuti katalog referensi workbook. Variasi merek dan harga di luar
                    toko sumber adalah simulasi pembanding; harga promo dan ketersediaan dapat
                    berubah. Verifikasi ke cabang sebelum belanja.
                </p>
                <div class="coordinates">
                    <label
                        >Latitude<input
                            id="lat"
                            type="number"
                            step="any"
                            value="-7.282356" /></label
                    ><label
                        >Longitude<input id="lng" type="number" step="any" value="112.794925"
                    /></label>
                </div>
                <button id="searchButton" class="planner-button secondary" type="button">
                    Cari toko terdekat
                </button>
                <div id="storesPanel" class="stores-panel" hidden>
                    <p id="storeCount" class="panel-hint">Toko di sekitar Anda</p>
                    <div id="storeList" class="store-list"></div>
                </div>
            </article>

            <article class="planner-card umkm-card">
                <div class="card-title">
                    <span class="step">B</span>
                    <div>
                        <h3>Menu UMKM sesuai pilihan Anda</h3>
                        <p>Temukan hidangan siap santap dari usaha kuliner lokal.</p>
                    </div>
                </div>
                <div id="umkmList" class="umkm-list" aria-live="polite">
                    <p class="empty-state">Pilih program untuk melihat menu UMKM yang sesuai.</p>
                </div>
            </article>

            <div class="wizard-actions">
                <span></span
                ><button class="planner-button primary" type="button" data-next-step="equipment">
                    Lanjut: pilih peralatan
                </button>
            </div>
        </section>

        <section class="planner-card wizard-step" data-step="equipment" hidden>
            <div class="card-title">
                <span class="step">02</span>
                <div>
                    <h2>Peralatan di rumah</h2>
                    <p>Pilih alat masak dan perlengkapan dapur yang tersedia agar saran resep sesuai kondisi rumah.</p>
                </div>
            </div>
            <fieldset class="equipment-options">
                <legend>Alat masak</legend>
                <label class="equipment-option"
                    ><input type="checkbox" name="equipment" value="kompor" /><span
                        >Kompor & wajan</span
                    ></label
                >
                <label class="equipment-option"
                    ><input type="checkbox" name="equipment" value="rice_cooker" /><span
                        >Rice cooker</span
                    ></label
                >
                <label class="equipment-option"
                    ><input type="checkbox" name="equipment" value="oven" /><span>Oven</span></label
                >
                <label class="equipment-option"
                    ><input type="checkbox" name="equipment" value="air_fryer" /><span
                        >Air fryer</span
                    ></label
                >
                <label class="equipment-option"
                    ><input type="checkbox" name="equipment" value="steamer" /><span
                        >Kukusan</span
                    ></label
                >
                <label class="equipment-option"
                    ><input type="checkbox" name="equipment" value="blender" /><span
                        >Blender</span
                    ></label
                >
            </fieldset>
            <fieldset class="equipment-options kitchen-tools">
                <legend>Kitchen set & perlengkapan</legend>
                <label class="equipment-option"
                    ><input type="checkbox" name="equipment" value="knife" /><span
                        >Pisau & talenan</span
                    ></label
                >
                <label class="equipment-option"
                    ><input type="checkbox" name="equipment" value="measuring_tools" /><span
                        >Gelas ukur / timbangan</span
                    ></label
                >
                <label class="equipment-option"
                    ><input type="checkbox" name="equipment" value="food_storage" /><span
                        >Wadah penyimpanan makanan</span
                    ></label
                >
            </fieldset>
            <p class="field-hint">Pilih minimal satu peralatan. Bahan dan langkah resep akan menyesuaikan pilihan Anda.</p>
            <div class="wizard-actions">
                <button class="planner-button secondary" type="button" data-prev-step="profile">
                    Kembali</button
                ><button class="planner-button primary" type="button" data-next-step="budget">
                    Lanjut: atur anggaran
                </button>
            </div>
        </section>

        <section class="planner-card budget-card wizard-step" data-step="budget" hidden>
            <div class="card-title">
                <span class="step">03</span>
                <div>
                    <h2>Anggaran & jadwal plan</h2>
                    <p>Tentukan batas belanja dan tanggal menu yang ingin dibuat.</p>
                </div>
            </div>
            <div class="budget-value"><span>Rp</span><output id="budgetValue">150.000</output></div>
            <input
                id="budget"
                class="budget-slider"
                type="range"
                min="25000"
                max="1000000"
                step="5000"
                value="150000"
            />
            <div class="slider-labels"><span>Rp 25 ribu</span><span>Rp 1 juta</span></div>
            <div class="date-fields">
                <label>Tanggal mulai<input id="startDate" type="date" /></label
                ><label id="endDateField" hidden
                    >Tanggal selesai<input id="endDate" type="date"
                /></label>
            </div>
            <aside class="membership-inline">
                <p class="eyebrow">MEMBERSHIP</p>
                <h3>Lebih banyak hari, lebih sedikit berpikir.</h3>
                <p>Member dapat menyusun plan untuk rentang tanggal lebih panjang.</p>
                <button id="membershipButton" class="planner-button dark" type="button">
                    Menjadi member</button
                ><small id="membershipNote">Status membership dimuat dari akun Anda.</small>
            </aside>
            <div class="wizard-actions">
                <button class="planner-button secondary" type="button" data-prev-step="equipment">
                    Kembali</button
                ><button id="generateButton" class="planner-button primary" type="button">
                    Generate meal plan
                </button>
            </div>
        </section>

        <section id="planPanel" class="planner-card wizard-step" data-step="results" hidden>
            <div class="card-title">
                <span class="step">04</span>
                <div>
                    <h2>Hasil meal plan Anda</h2>
                    <p id="planType">Daily plan</p>
                </div>
            </div>
            <p id="bmiSummary" class="bmi-summary" role="status"></p>
            <div class="plan-summary">
                <div><small>Anggaran per hari</small><strong id="dailyBudget">Rp 0</strong></div>
                <div>
                    <small>Anggaran seluruh plan</small><strong id="totalBudget">Rp 0</strong>
                </div>
                <div>
                    <small>Total belanja seluruh plan</small><strong id="totalCost">Rp 0</strong>
                </div>
                <div>
                    <small>Sisa anggaran seluruh plan</small
                    ><strong id="remainingBudget">Rp 0</strong>
                </div>
                <div>
                    <small>Kalori plan / hari</small><strong id="totalCalories">0 kcal</strong>
                </div>
                <div>
                    <small>Target karbohidrat / hari</small
                    ><strong id="maxCarbs">Belum diatur</strong>
                </div>
            </div>
            <p id="equipmentSummary" class="field-hint"></p>
            <button id="saveCalendarButton" class="planner-button primary" type="button">
                Simpan plan ke kalender
            </button>
            <div class="plan-columns">
                <div>
                    <h3>Daftar belanja</h3>
                    <ul id="shoppingList" class="shopping-list"></ul>
                </div>
                <div>
                    <h3>Menu plan</h3>
                    <div id="mealPlan" class="meal-plan"></div>
                </div>
            </div>
            <article class="umkm-results">
                <h3>Menu UMKM sesuai target Anda</h3>
                <p class="panel-hint">Dukung usaha lokal dan cek langsung ketersediaan menu ke penjual.</p>
                <div id="resultUmkmList" class="umkm-list"></div>
            </article>
            <div class="wizard-actions">
                <button class="planner-button secondary" type="button" data-prev-step="budget">
                    Kembali ke anggaran</button
                ><button class="planner-button primary" type="button" data-go-step="profile">
                    Buat plan baru
                </button>
            </div>
        </section>
    </div>

    <div id="muscleModal" class="modal-backdrop" hidden>
        <section
            class="muscle-dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="muscleDialogTitle"
        >
            <button id="closeMuscleModal" class="modal-close" type="button" aria-label="Tutup">
                &times;
            </button>
            <p class="eyebrow">BINARAGA</p>
            <h2 id="muscleDialogTitle">Pilih area otot target</h2>
            <p>Pilih satu atau beberapa bagian pada ilustrasi.</p>
            <div class="muscle-map-layout">
                <svg
                    class="muscle-map"
                    viewBox="0 0 500 360"
                    role="img"
                    aria-label="Ilustrasi tampak depan dan belakang untuk memilih area otot"
                >
                    <circle cx="120" cy="35" r="23" class="body-base"></circle>
                    <path
                        class="body-base"
                        d="M91 65 Q120 54 149 65 L165 133 151 187 146 230 158 315 135 320 120 244 105 320 82 315 94 230 89 187 75 133Z"
                    ></path>
                    <path
                        class="body-base"
                        d="M76 77 52 91 34 166 48 171 72 118 91 104M164 77 188 91 206 166 192 171 168 118 149 104"
                    ></path>
                    <text x="120" y="345" class="body-label">DEPAN</text>
                    <g
                        class="muscle-zone"
                        role="button"
                        tabindex="0"
                        aria-pressed="false"
                        data-muscle="Dada"
                        aria-label="Pilih otot dada"
                    >
                        <path d="M91 86 Q120 75 149 86 L145 119 Q120 132 95 119Z"></path>
                        <text x="120" y="107">DADA</text>
                    </g>
                    <g
                        class="muscle-zone"
                        role="button"
                        tabindex="0"
                        aria-pressed="false"
                        data-muscle="Bahu"
                        aria-label="Pilih otot bahu"
                    >
                        <ellipse cx="82" cy="77" rx="13" ry="11"></ellipse>
                        <ellipse cx="158" cy="77" rx="13" ry="11"></ellipse>
                        <text x="120" y="73">BAHU</text>
                    </g>
                    <g
                        class="muscle-zone"
                        role="button"
                        tabindex="0"
                        aria-pressed="false"
                        data-muscle="Lengan"
                        aria-label="Pilih otot lengan"
                    >
                        <path d="M57 98 72 105 54 157 44 155Z M183 98 168 105 186 157 196 155Z"></path>
                        <text x="120" y="153">LENGAN</text>
                    </g>
                    <g
                        class="muscle-zone"
                        role="button"
                        tabindex="0"
                        aria-pressed="false"
                        data-muscle="Perut"
                        aria-label="Pilih otot perut"
                    >
                        <path d="M99 125 141 125 138 181 102 181Z"></path>
                        <text x="120" y="158">PERUT</text>
                    </g>
                    <g
                        class="muscle-zone"
                        role="button"
                        tabindex="0"
                        aria-pressed="false"
                        data-muscle="Glutes"
                        aria-label="Pilih otot glutes"
                    >
                        <path d="M94 185 120 190 146 185 143 220 120 228 97 220Z"></path>
                        <text x="120" y="210">GLUTES</text>
                    </g>
                    <g
                        class="muscle-zone"
                        role="button"
                        tabindex="0"
                        aria-pressed="false"
                        data-muscle="Kaki"
                        aria-label="Pilih otot kaki"
                    >
                        <path d="M97 229 117 233 104 304 85 302Z M123 233 143 229 155 302 136 304Z"></path>
                        <text x="120" y="275">KAKI</text>
                    </g>
                    <circle cx="370" cy="35" r="23" class="body-base"></circle>
                    <path
                        class="body-base"
                        d="M341 65 Q370 54 399 65 L415 133 401 187 396 230 408 315 385 320 370 244 355 320 332 315 344 230 339 187 325 133Z"
                    ></path>
                    <path
                        class="body-base"
                        d="M326 77 302 91 284 166 298 171 322 118 341 104M414 77 438 91 456 166 442 171 418 118 399 104"
                    ></path>
                    <g
                        class="muscle-zone"
                        role="button"
                        tabindex="0"
                        aria-pressed="false"
                        data-muscle="Punggung"
                        aria-label="Pilih otot punggung"
                    >
                        <path d="M342 84 Q370 75 398 84 L394 179 370 190 346 179Z"></path>
                        <text x="370" y="136">PUNGGUNG</text>
                    </g>
                    <text x="370" y="345" class="body-label">BELAKANG</text>
                </svg>
                <div class="muscle-legend" aria-label="Pilihan area otot">
                    <button
                        type="button"
                        class="muscle-choice"
                        data-muscle-choice="Dada"
                        aria-pressed="false"
                    >
                        Dada
                    </button>
                    <button
                        type="button"
                        class="muscle-choice"
                        data-muscle-choice="Bahu"
                        aria-pressed="false"
                    >
                        Bahu
                    </button>
                    <button
                        type="button"
                        class="muscle-choice"
                        data-muscle-choice="Lengan"
                        aria-pressed="false"
                    >
                        Lengan
                    </button>
                    <button
                        type="button"
                        class="muscle-choice"
                        data-muscle-choice="Perut"
                        aria-pressed="false"
                    >
                        Perut
                    </button>
                    <button
                        type="button"
                        class="muscle-choice"
                        data-muscle-choice="Punggung"
                        aria-pressed="false"
                    >
                        Punggung
                    </button>
                    <button
                        type="button"
                        class="muscle-choice"
                        data-muscle-choice="Glutes"
                        aria-pressed="false"
                    >
                        Glutes
                    </button>
                    <button
                        type="button"
                        class="muscle-choice"
                        data-muscle-choice="Kaki"
                        aria-pressed="false"
                    >
                        Kaki
                    </button>
                </div>
            </div>
            <button id="saveMuscles" class="planner-button primary" type="button">
                Simpan area otot
            </button>
        </section>
    </div>
@endsection

@push ('scripts')
    @vite (['resources/css/planner.css', 'resources/js/planner.js'])
@endpush
