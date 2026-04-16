<x-layouts.app title="Zapomenuté heslo — Rajón">
    <div class="mx-auto max-w-md">
        <h1 class="mb-6 text-2xl font-bold text-gray-800">Zapomenuté heslo</h1>

        @if (session('status'))
            <div class="mb-4 rounded-lg bg-green-50 border border-green-200 p-4 text-sm text-green-700">
                {{ session('status') }}
            </div>
        @endif

        <p class="mb-4 text-sm text-gray-600">Zadejte svůj e-mail a my vám pošleme odkaz pro obnovení hesla.</p>

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf
            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-gray-700">E-mail</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none">
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <button type="submit"
                class="w-full rounded-lg bg-primary px-4 py-3 text-sm font-medium text-white hover:bg-primary-dark transition">
                Odeslat odkaz
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-gray-500">
            <a href="{{ route('login') }}" class="font-medium text-primary hover:text-primary-dark">Zpět na přihlášení</a>
        </p>
    </div>
</x-layouts.app>
