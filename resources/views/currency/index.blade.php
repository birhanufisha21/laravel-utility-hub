@extends('layouts.app')

@section('title', 'Currency Converter')

@section('content')
<div
    x-data="{
        amount: '',
        from: 'USD',
        to: 'EUR',
        loading: false,
        result: null,
        error: null,

        currencies: [
            'AED','AFN','ALL','AMD','ANG','AOA','ARS','AUD','AWG','AZN',
            'BAM','BBD','BDT','BGN','BHD','BIF','BMD','BND','BOB','BRL',
            'BSD','BTN','BWP','BYN','BZD','CAD','CDF','CHF','CLP','CNY',
            'COP','CRC','CUP','CVE','CZK','DJF','DKK','DOP','DZD','EGP',
            'ERN','ETB','EUR','FJD','FKP','GBP','GEL','GHS','GIP','GMD',
            'GNF','GTQ','GYD','HKD','HNL','HRK','HTG','HUF','IDR','ILS',
            'INR','IQD','IRR','ISK','JMD','JOD','JPY','KES','KGS','KHR',
            'KMF','KPW','KRW','KWD','KYD','KZT','LAK','LBP','LKR','LRD',
            'LSL','LYD','MAD','MDL','MGA','MKD','MMK','MNT','MOP','MRU',
            'MUR','MVR','MWK','MXN','MYR','MZN','NAD','NGN','NIO','NOK',
            'NPR','NZD','OMR','PAB','PEN','PGK','PHP','PKR','PLN','PYG',
            'QAR','RON','RSD','RUB','RWF','SAR','SBD','SCR','SDG','SEK',
            'SGD','SHP','SLL','SOS','SRD','STN','SVC','SYP','SZL','THB',
            'TJS','TMT','TND','TOP','TRY','TTD','TWD','TZS','UAH','UGX',
            'USD','UYU','UZS','VES','VND','VUV','WST','XAF','XCD','XOF',
            'XPF','YER','ZAR','ZMW','ZWL'
        ],

        async submit() {
            if (!this.amount || parseFloat(this.amount) <= 0) {
                this.error = 'Please enter a valid amount greater than 0.';
                return;
            }

            this.loading = true;
            this.result  = null;
            this.error   = null;

            try {
                const res = await fetch('/api/currency/convert', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify({
                        amount: parseFloat(this.amount),
                        from: this.from,
                        to: this.to,
                    }),
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
        <h1 class="text-3xl font-bold mb-2">💱 Currency Converter</h1>
        <p class="text-slate-400">Live exchange rates powered by ExchangeRate-API</p>
    </div>

    <!-- Card -->
    <div class="bg-slate-800 rounded-2xl shadow-xl p-8 border border-slate-700">

        <!-- Form -->
        <form @submit.prevent="submit" novalidate>

            <!-- Amount -->
            <div class="mb-5">
                <label for="amount" class="block text-sm font-medium text-slate-300 mb-1">Amount</label>
                <input
                    id="amount"
                    type="number"
                    min="0.01"
                    step="any"
                    x-model="amount"
                    placeholder="e.g. 100"
                    class="w-full bg-slate-700 border border-slate-600 rounded-lg px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                    :disabled="loading"
                    required
                >
            </div>

            <!-- From / To currencies -->
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label for="from" class="block text-sm font-medium text-slate-300 mb-1">From</label>
                    <select
                        id="from"
                        x-model="from"
                        class="w-full bg-slate-700 border border-slate-600 rounded-lg px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        :disabled="loading"
                    >
                        <template x-for="code in currencies" :key="code">
                            <option :value="code" x-text="code" :selected="code === from"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label for="to" class="block text-sm font-medium text-slate-300 mb-1">To</label>
                    <select
                        id="to"
                        x-model="to"
                        class="w-full bg-slate-700 border border-slate-600 rounded-lg px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        :disabled="loading"
                    >
                        <template x-for="code in currencies" :key="code">
                            <option :value="code" x-text="code" :selected="code === to"></option>
                        </template>
                    </select>
                </div>
            </div>

            <!-- Submit button -->
            <button
                type="submit"
                :disabled="loading"
                class="w-full bg-blue-600 hover:bg-blue-500 disabled:bg-slate-600 disabled:cursor-not-allowed text-white font-semibold py-3 px-6 rounded-lg transition-colors flex items-center justify-center gap-2"
            >
                <span x-show="loading" class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                <span x-text="loading ? 'Converting…' : 'Convert'"></span>
            </button>
        </form>

        <!-- Result -->
        <div x-show="result" x-cloak class="mt-6 p-5 bg-slate-700/50 rounded-xl border border-slate-600">
            <p class="text-slate-400 text-sm mb-1">Result</p>
            <p class="text-3xl font-bold text-white">
                <span x-text="result?.converted_amount?.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                <span x-text="result?.to" class="text-blue-400 ml-1"></span>
            </p>
            <p class="text-slate-400 text-sm mt-2">
                <span x-text="result?.amount?.toLocaleString()"></span>
                <span x-text="result?.from"></span>
                = <span x-text="result?.converted_amount?.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                <span x-text="result?.to"></span>
            </p>
            <p class="text-slate-500 text-xs mt-1">
                Rate: 1 <span x-text="result?.from"></span> = <span x-text="result?.rate"></span> <span x-text="result?.to"></span>
            </p>
        </div>

        <!-- Error -->
        <div x-show="error" x-cloak class="mt-6 p-4 bg-red-900/40 border border-red-700 rounded-xl text-red-300 text-sm">
            ⚠️ <span x-text="error"></span>
        </div>

    </div>
</div>
@endsection
