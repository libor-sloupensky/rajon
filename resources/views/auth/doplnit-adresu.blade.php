<x-layouts.app title="Doplnit adresu — Rajón">
    <div class="mx-auto max-w-lg">
        <h1 class="mb-2 text-2xl font-bold text-gray-800">Doplňte své sídlo</h1>
        <p class="mb-6 text-sm text-gray-600">
            Pro výpočet vzdálenosti k akcím potřebujeme vědět, kde sídlíte.
            Stačí město; PSČ pomáhá odlišit obce se stejným názvem.
        </p>

        @if(session('success'))
            <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-2 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ url('/doplnit-adresu') }}" class="space-y-4">
            @csrf

            <div class="grid grid-cols-3 gap-4">
                <div class="col-span-2">
                    <label for="mesto" class="mb-1 block text-sm font-medium text-gray-700">Město <span class="text-red-500">*</span></label>
                    <input type="text" id="mesto" name="mesto" value="{{ old('mesto', Auth::user()->mesto) }}" required
                        placeholder="např. Praha"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:outline-none">
                    @error('mesto') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="psc" class="mb-1 block text-sm font-medium text-gray-700">PSČ <span class="text-gray-400">(volitelné)</span></label>
                    <input type="text" id="psc" name="psc" value="{{ old('psc', Auth::user()->psc) }}"
                        placeholder="11000" maxlength="10"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:outline-none">
                    @error('psc') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <button type="submit" class="w-full rounded-lg bg-primary px-4 py-3 text-sm font-medium text-white hover:bg-primary-dark transition">
                Uložit a pokračovat
            </button>
        </form>
    </div>
</x-layouts.app>
