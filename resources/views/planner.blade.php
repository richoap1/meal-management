@extends('layouts.app')

@section('content')
<div class="meal-page">
    <section class="meal-hero">
        <div><p class="eyebrow">SMART MEAL PLANNER</p><h1>Plan meals that fit <span>your day.</span></h1><p class="hero-copy">Find the nearest supermarket, choose your budget, and get a plan made for you.</p></div>
        <div class="hero-badge"><span>●</span> Location ready when you are</div>
    </section>
    <div id="alert" class="planner-alert" role="alert" hidden></div>
    <section class="planner-grid">
        <div class="planner-main">
            <article class="planner-card">
                <div class="card-title"><span class="step">01</span><div><h2>Find nearby stores</h2><p>Use your live location or enter it manually.</p></div></div>
                <div class="location-actions"><button id="locateButton" class="planner-button primary" type="button">Use live location</button><span id="locationStatus" class="location-status">Location not set</span></div>
                <div class="coordinates"><label>Latitude<input id="lat" type="number" step="any" value="-7.282356"></label><label>Longitude<input id="lng" type="number" step="any" value="112.794925"></label></div>
                <button id="searchButton" class="planner-button secondary" type="button">Search nearest stores</button>
            </article>
            <article id="storesPanel" class="planner-card" hidden><div class="card-title"><span class="step">02</span><div><h2>Choose a store</h2><p id="storeCount">Stores near your location</p></div></div><div id="storeList" class="store-list"></div></article>
            <article id="planPanel" class="planner-card" hidden><div class="card-title"><span class="step">04</span><div><h2>Your meal plan</h2><p id="planType">Daily plan</p></div></div><div class="plan-summary"><div><small>Total shopping</small><strong id="totalCost">Rp 0</strong></div><div><small>Budget left</small><strong id="remainingBudget">Rp 0</strong></div><div><small>Total calories</small><strong id="totalCalories">0 kcal</strong></div><div><small>Max carbs / day</small><strong id="maxCarbs">Not set</strong></div></div><button id="saveCalendarButton" class="planner-button primary" type="button">Save plan to calendar</button><div class="plan-columns"><div><h3>Shopping list</h3><ul id="shoppingList" class="shopping-list"></ul></div><div><h3>Meals</h3><div id="mealPlan" class="meal-plan"></div></div></div></article>
        </div>
        <aside class="planner-side">
            <article class="planner-card budget-card" id="budgetPanel" hidden>
                <div class="card-title"><span class="step">03</span><div><h2>Set your budget</h2><p>Drag the slider to choose your limit.</p></div></div>
                <div class="budget-value"><span>Rp</span><output id="budgetValue">150.000</output></div><input id="budget" class="budget-slider" type="range" min="25000" max="1000000" step="5000" value="150000"><div class="slider-labels"><span>Rp 25k</span><span>Rp 1m</span></div>
                <div class="mode-switch" role="group" aria-label="Meal plan mode"><button id="nonDietButton" class="mode-option active" type="button">Non-diet</button><button id="dietButton" class="mode-option" type="button">Diet</button></div>
                <div class="date-fields"><label>Start date<input id="startDate" type="date"></label><label id="endDateField" hidden>End date<input id="endDate" type="date"></label></div>
                <div id="dietFields" class="diet-fields" hidden><p class="diet-hint">We use a moderate calorie deficit estimate. For medical conditions, follow your doctor's advice.</p><div class="diet-inputs"><label>Sex<select id="sex"><option value="female">Female</option><option value="male">Male</option></select></label><label>Weight (kg)<input id="weight" type="number" min="20" max="300" step=".1" placeholder="60"></label><label>Age<input id="age" type="number" min="13" max="100" step="1" placeholder="25"></label><label>Height (cm)<input id="height" type="number" min="100" max="230" step=".1" placeholder="165"></label></div></div>
                <button id="generateButton" class="planner-button primary" type="button">Generate meal plan</button>
            </article>
            <article class="planner-card member-card"><p class="eyebrow">MEMBERSHIP</p><h2>More days, less thinking.</h2><p>Members can plan any date range. Free users get a focused plan for today.</p><button id="membershipButton" class="planner-button dark" type="button">Become a member</button><small id="membershipNote">Your membership is saved for this session.</small></article>
        </aside>
    </section>
</div>
@endsection

@push('scripts')
@vite(['resources/css/planner.css', 'resources/js/planner.js'])
@endpush