<x-layouts.app title="Ověření e-mailu — Rajón">
    <div class="mx-auto max-w-md">
        <h1 class="mb-6 text-2xl font-bold text-gray-800">Ověření e-mailu</h1>

        @if (session('status') == 'verification-link-sent')
            <div class="mb-4 rounded-lg bg-green-50 border border-green-200 p-4 text-sm text-green-700">
                Nový ověřovací odkaz byl odeslán na váš e-mail.
            </div>
        @endif

        <p class="mb-4 text-sm text-gray-600">
            Děkujeme za registraci! Před pokračováním prosím ověřte svůj e-mail kliknutím na odkaz, který jsme vám zaslali.
        </p>

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit"
                class="w-full rounded-lg bg-primary px-4 py-3 text-sm font-medium text-white hover:bg-primary-dark transition">
                Znovu odeslat ověřovací e-mail
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="mt-4">
            @csrf
            <button type="submit" class="text-sm text-gray-500 hover:text-gray-700">Odhlásit se</button>
        </form>
    </div>
</x-layouts.app>
