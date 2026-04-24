<x-layouts.app title="Nová pozvánka — Rajón">
    <div class="max-w-2xl">
        <a href="{{ route('admin.pozvanky.index') }}" class="text-sm text-primary hover:text-primary-dark mb-4 inline-block">&larr; Zpět</a>
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Nová pozvánka</h1>

        <form method="POST" action="{{ route('admin.pozvanky.store') }}" class="rounded-lg border border-gray-200 bg-white p-4 space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">E-mail <span class="text-red-500">*</span></label>
                <input type="email" name="email" required value="{{ old('email') }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:outline-none">
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jméno</label>
                    <input type="text" name="jmeno" value="{{ old('jmeno') }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Příjmení</label>
                    <input type="text" name="prijmeni" value="{{ old('prijmeni') }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role <span class="text-red-500">*</span></label>
                    <select name="role" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        <option value="fransizan" {{ old('role') === 'fransizan' ? 'selected' : '' }}>Franšízant</option>
                        <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Administrátor</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Platnost (dny)</label>
                    <input type="number" name="platnost_dni" value="{{ old('platnost_dni', 14) }}" min="1" max="365"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Region <span class="text-gray-400">(nepovinný)</span></label>
                <input type="text" name="region" value="{{ old('region') }}" placeholder="např. Jihomoravský kraj"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Poznámka</label>
                <textarea name="poznamka" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('poznamka') }}</textarea>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="flex-1 rounded-lg bg-primary px-4 py-3 text-sm font-medium text-white hover:bg-primary-dark">
                    Vytvořit a odeslat pozvánku
                </button>
            </div>
        </form>
    </div>
</x-layouts.app>
