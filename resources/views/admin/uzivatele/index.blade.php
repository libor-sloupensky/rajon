<x-layouts.app title="Uživatelé — Rajón">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Uživatelé</h1>
    </div>

    {{-- Rychlé pozvání --}}
    <details class="mb-8 rounded-lg border border-gray-200 bg-white overflow-hidden" {{ $errors->any() ? 'open' : '' }}>
        <summary class="cursor-pointer bg-primary/5 px-4 py-3 font-medium text-primary hover:bg-primary/10">
            + Pozvat nového uživatele
        </summary>
        <form method="POST" action="{{ route('admin.uzivatele.pozvat') }}" class="p-4 space-y-3">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">E-mail <span class="text-red-500">*</span></label>
                    <input type="email" name="email" required value="{{ old('email') }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:outline-none">
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role <span class="text-red-500">*</span></label>
                    <select name="role" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        <option value="fransizan" {{ old('role') === 'fransizan' ? 'selected' : '' }}>Franšízant</option>
                        <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Administrátor</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
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

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Region</label>
                    <input type="text" name="region" value="{{ old('region') }}" placeholder="např. Jihomoravský kraj"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Platnost pozvánky (dny)</label>
                    <input type="number" name="platnost_dni" value="{{ old('platnost_dni', 14) }}" min="1" max="365"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
            </div>

            <button type="submit" class="rounded-lg bg-primary px-6 py-2 text-sm font-medium text-white hover:bg-primary-dark transition">
                Odeslat pozvánku
            </button>
        </form>
    </details>

    {{-- Aktivní pozvánky --}}
    @if($pozvankyAktivni->isNotEmpty())
        <div class="mb-8">
            <h2 class="text-lg font-semibold text-gray-700 mb-3">Čekající pozvánky ({{ $pozvankyAktivni->count() }})</h2>
            <div class="space-y-2">
                @foreach($pozvankyAktivni as $p)
                    <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-3">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap mb-1">
                                    <span class="font-medium text-gray-800">{{ $p->email }}</span>
                                    @if($p->jmeno || $p->prijmeni)
                                        <span class="text-sm text-gray-500">({{ trim("{$p->jmeno} {$p->prijmeni}") }})</span>
                                    @endif
                                    <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700">{{ $p->role }}</span>
                                    @if($p->plati_do?->isPast())
                                        <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">expirovaná</span>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-500 space-x-3">
                                    @if($p->pozval)
                                        <span>Pozval: {{ $p->pozval->celejmeno() }}</span>
                                    @endif
                                    <span>Platí do: {{ $p->plati_do?->format('j. n. Y H:i') }}</span>
                                </div>
                                <input type="text" readonly value="{{ $p->url() }}"
                                    class="mt-2 w-full text-xs bg-white border border-yellow-200 rounded px-2 py-1 font-mono"
                                    onclick="this.select()">
                            </div>
                            <div class="flex gap-2 shrink-0">
                                <form method="POST" action="{{ route('admin.uzivatele.pozvanka.resend', $p) }}">
                                    @csrf
                                    <button type="submit" class="rounded border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-50">
                                        Znovu odeslat
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.uzivatele.pozvanka.zrusit', $p) }}"
                                    onsubmit="return confirm('Zrušit pozvánku?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded border border-red-300 bg-white px-3 py-1.5 text-xs text-red-600 hover:bg-red-50">
                                        Zrušit
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Uživatelé --}}
    <div class="mb-8">
        <h2 class="text-lg font-semibold text-gray-700 mb-3">Registrovaní uživatelé ({{ $uzivatele->total() }})</h2>
        @if($uzivatele->isEmpty())
            <p class="text-gray-500">Zatím žádní uživatelé.</p>
        @else
            <div class="rounded-lg border border-gray-200 bg-white overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200 text-xs text-gray-500 uppercase">
                        <tr>
                            <th class="text-left px-4 py-2">Jméno</th>
                            <th class="text-left px-4 py-2">E-mail</th>
                            <th class="text-left px-4 py-2">Role</th>
                            <th class="text-left px-4 py-2">Region</th>
                            <th class="text-left px-4 py-2">Registrace</th>
                            <th class="text-left px-4 py-2">Ověřen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($uzivatele as $u)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2">{{ $u->celejmeno() }}</td>
                                <td class="px-4 py-2 text-gray-600">{{ $u->email }}</td>
                                <td class="px-4 py-2">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium
                                        {{ $u->role === 'admin' ? 'bg-primary/10 text-primary' : 'bg-gray-100 text-gray-600' }}">
                                        {{ $u->role }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-gray-500">{{ $u->region ?? '—' }}</td>
                                <td class="px-4 py-2 text-xs text-gray-500">{{ $u->vytvoreno?->format('j. n. Y') }}</td>
                                <td class="px-4 py-2">
                                    @if($u->email_overen_v)
                                        <span class="text-green-600 text-xs">✓</span>
                                    @else
                                        <span class="text-gray-400 text-xs">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $uzivatele->links() }}</div>
        @endif
    </div>

    {{-- Historie pozvánek --}}
    @if($pozvankyHistorie->isNotEmpty())
        <div>
            <h2 class="text-lg font-semibold text-gray-700 mb-3">Historie pozvánek (posledních {{ $pozvankyHistorie->count() }})</h2>
            <div class="space-y-1 text-sm">
                @foreach($pozvankyHistorie as $p)
                    <div class="flex items-center justify-between rounded border border-gray-200 bg-white px-3 py-2">
                        <div class="flex items-center gap-2">
                            <span>{{ $p->email }}</span>
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium
                                @if($p->stav === 'prijata') bg-green-100 text-green-700
                                @elseif($p->stav === 'zrusena') bg-gray-100 text-gray-700
                                @else bg-red-100 text-red-700
                                @endif">{{ $p->stav }}</span>
                        </div>
                        <div class="text-xs text-gray-500">
                            @if($p->prijata_v)
                                Přijata: {{ $p->prijata_v->format('j. n. Y H:i') }}
                            @else
                                {{ $p->vytvoreno?->diffForHumans() }}
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</x-layouts.app>
