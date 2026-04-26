<x-layouts.app title="Registrace — Rajón">
    <div class="mx-auto max-w-lg">
        <h1 class="mb-6 text-2xl font-bold text-gray-800">Dokončit registraci</h1>

        @if(isset($pozvanka) && $pozvanka)
            <div class="mb-6 rounded-lg border border-primary/30 bg-primary/5 p-4">
                <p class="text-sm text-gray-700">
                    <strong>{{ $pozvanka->pozval?->celejmeno() ?? 'Tým Rajón' }}</strong>
                    vás zve do aplikace Rajón jako
                    <strong>{{ $pozvanka->role === 'admin' ? 'administrátora' : 'franšízanta' }}</strong>.
                </p>
                @if($pozvanka->region)
                    <p class="mt-1 text-xs text-gray-500">Region: <strong>{{ $pozvanka->region }}</strong></p>
                @endif
            </div>
        @endif

        <form method="POST" action="{{ route('registrace.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $pozvanka->token ?? '' }}">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="jmeno" class="mb-1 block text-sm font-medium text-gray-700">Jméno</label>
                    <input type="text" id="jmeno" name="jmeno" value="{{ old('jmeno', $pozvanka->jmeno ?? '') }}" required
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:outline-none">
                    @error('jmeno') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="prijmeni" class="mb-1 block text-sm font-medium text-gray-700">Příjmení</label>
                    <input type="text" id="prijmeni" name="prijmeni" value="{{ old('prijmeni', $pozvanka->prijmeni ?? '') }}" required
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:outline-none">
                    @error('prijmeni') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-gray-700">E-mail</label>
                <input type="email" id="email" name="email" value="{{ old('email', $pozvanka->email ?? '') }}" required readonly
                    class="w-full rounded-lg border border-gray-300 bg-gray-100 px-3 py-2 text-sm">
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="telefon" class="mb-1 block text-sm font-medium text-gray-700">Telefon <span class="text-gray-400">(nepovinný)</span></label>
                <input type="tel" id="telefon" name="telefon" value="{{ old('telefon') }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:outline-none">
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div class="col-span-2">
                    <label for="mesto" class="mb-1 block text-sm font-medium text-gray-700">Sídlo — město <span class="text-red-500">*</span></label>
                    <input type="text" id="mesto" name="mesto" value="{{ old('mesto') }}" required
                        placeholder="např. Praha"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:outline-none">
                    @error('mesto') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="psc" class="mb-1 block text-sm font-medium text-gray-700">PSČ <span class="text-red-500">*</span></label>
                    <input type="text" id="psc" name="psc" value="{{ old('psc') }}" required
                        placeholder="11000" maxlength="6" pattern="\d{3}\s?\d{2}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:outline-none">
                    @error('psc') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
            <p class="text-xs text-gray-500 -mt-2">Adresa slouží pro výpočet vzdálenosti k akcím.</p>

            <div>
                <label for="password" class="mb-1 block text-sm font-medium text-gray-700">Heslo</label>
                <input type="password" id="password" name="password" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:outline-none">
                @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="mb-1 block text-sm font-medium text-gray-700">Heslo znovu</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:outline-none">
            </div>

            <button type="submit" class="w-full rounded-lg bg-primary px-4 py-3 text-sm font-medium text-white hover:bg-primary-dark transition">
                Dokončit registraci
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-gray-500">
            Už máte účet? <a href="{{ route('login') }}" class="font-medium text-primary hover:text-primary-dark">Přihlaste se</a>
        </p>
    </div>
</x-layouts.app>
