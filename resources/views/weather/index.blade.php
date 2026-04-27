@extends('layouts.app')

@section('title', 'Weather Dashboard')

@section('content')
<div
    x-data="{
        city: '',
        loading: false,
        result: null,
        error: null,

        weatherIcon(condition) {
            const c = (condition ?? '').toLowerCase();
            if (c.includes('clear') || c.includes('sunny'))   return '☀️';
            if (c.includes('mainly clear'))                    return '🌤';
            if (c.includes('partly cloudy'))                   return '⛅';
            if (c.includes('overcast'))                        return '☁️';
            if (c.includes('fog') || c.includes('icy fog'))    return '🌫';
            if (c.includes('drizzle'))                         return '🌦';
            if (c.includes('freezing'))                        return '🌨';
            if (c.includes('rain'))                            return '🌧';
            if (c.includes('snow') || c.includes('snow grain'))return '❄️';
            if (c.includes('thunder'))                         return '⛈';
            return '🌡';
        },

        async submit() {
            if (!this.city.trim()) {
                this.error = 'Please enter a city name.';
                return;
            }

            this.loading = true;
            this.result  = null;
            this.error   = null;

            try {
                const params = new URLSearchParams({ city: this.city.trim() });
                const res = await fetch('/api/weather/search?' + params.toString(), {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                    },
                });

                const data = await res.json();

                if (!res.ok) {
                    this.error = data.message ?? 'Something went wrong. Please try again.';
                    return;
                }

                this.result = data;
            } catch (e) {
                this.error = 'Network error. Please check your connection and try again.';
            } finally {
                this.loading = false;
            }
        }
    }"
    class="max-w-xl mx-auto"
>
    <!-- Header -->
    <div class="mb-8 text-center">
        <h1 class="text-3xl font-bold mb-2">🌤 Weather Dashboard</h1>
        <p class="text-slate-400">Current conditions powered by Open-Meteo — no API key required</p>
    </div>

    <!-- Card -->
    <div class="bg-slate-800 rounded-2xl shadow-xl p-8 border border-slate-700">

        <!-- Search form -->
        <form @submit.prevent="submit" novalidate>
            <div class="mb-5">
                <label for="city" class="block text-sm font-medium text-slate-300 mb-1">City</label>
                <input
                    id="city"
                    type="text"
                    x-model="city"
                    placeholder="e.g. London, Tokyo, New York"
                    maxlength="100"
                    class="w-full bg-slate-700 border border-slate-600 rounded-lg px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                    :disabled="loading"
                    required
                >
            </div>

            <!-- Submit button -->
            <button
                type="submit"
                :disabled="loading"
                class="w-full bg-blue-600 hover:bg-blue-500 disabled:bg-slate-600 disabled:cursor-not-allowed text-white font-semibold py-3 px-6 rounded-lg transition-colors flex items-center justify-center gap-2"
            >
                <span x-show="loading" class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                <span x-text="loading ? 'Searching…' : 'Get Weather'"></span>
            </button>
        </form>

        <!-- Result -->
        <div x-show="result" x-cloak class="mt-6">

            <!-- City name & condition -->
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-2xl font-bold text-white">
                        <span x-text="result?.city"></span>
                        <span class="text-slate-400 text-lg font-normal ml-1" x-text="result?.country ? '(' + result.country + ')' : ''"></span>
                    </h2>
                    <p class="text-slate-300 mt-1">
                        <span x-text="weatherIcon(result?.condition)"></span>
                        <span x-text="result?.condition"></span>
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-5xl font-bold text-white" x-text="result?.temperature_c + '°C'"></p>
                    <p class="text-slate-400 text-sm mt-1" x-text="result?.temperature_f + '°F'"></p>
                </div>
            </div>

            <!-- Stats grid -->
            <div class="grid grid-cols-2 gap-3 mt-4">
                <div class="bg-slate-700/50 rounded-xl p-4 border border-slate-600">
                    <p class="text-slate-400 text-xs uppercase tracking-wide mb-1">Humidity</p>
                    <p class="text-xl font-semibold text-white" x-text="result?.humidity + '%'"></p>
                </div>
                <div class="bg-slate-700/50 rounded-xl p-4 border border-slate-600">
                    <p class="text-slate-400 text-xs uppercase tracking-wide mb-1">Wind Speed</p>
                    <p class="text-xl font-semibold text-white" x-text="result?.wind_speed_kmh + ' km/h'"></p>
                </div>
            </div>
        </div>

        <!-- Error -->
        <div x-show="error" x-cloak class="mt-6 p-4 bg-red-900/40 border border-red-700 rounded-xl text-red-300 text-sm">
            ⚠️ <span x-text="error"></span>
        </div>

    </div>
</div>
@endsection
