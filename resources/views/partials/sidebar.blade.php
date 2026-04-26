{{-- Sdílený sidebar --}}
@auth
    @php
        $navUser = Auth::user();
        $navIsAdmin = $navUser->jeAdmin();
    @endphp

    <style>
        .rj-sidebar { width: 15rem; transition: margin-left 0.2s; }
        .rj-sidebar.collapsed { margin-left: -15rem; }
        .rj-sidebar-section-body { transition: max-height 0.2s; overflow: hidden; }
        .rj-sidebar-section-body.collapsed { max-height: 0 !important; }
        .rj-sidebar a.active { color: #dd5500; font-weight: 600; background: rgba(221, 85, 0, 0.1); border-radius: 0.375rem; }
        .rj-sidebar a:hover:not(.active) { color: #dd5500; font-weight: 600; }
    </style>

    <aside id="rj-sidebar" class="rj-sidebar flex-shrink-0 bg-white border-r border-gray-200 sticky top-14 z-30">
        <nav class="py-3 px-2 space-y-2">

            {{-- AKCE --}}
            <div class="rj-sidebar-section rounded-lg border border-primary bg-primary/5 ring-1 ring-primary p-1">
                <div class="px-2 py-1.5 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Akce</div>
                <div class="rj-sidebar-section-body" style="max-height: 20rem;">
                    @php
                        $jeKatalog = (request()->is('akce') || request()->is('akce/*')) && !request()->boolean('moje_rezervovane');
                        $jeMojeRez = (request()->is('akce') || request()->is('akce/*')) && request()->boolean('moje_rezervovane');
                        $jeMapa = request()->is('mapa');
                    @endphp
                    <a href="{{ url('/dashboard') }}" class="block px-3 py-1.5 text-sm text-gray-600 rounded {{ request()->is('dashboard') ? 'active' : '' }}">Dashboard</a>
                    <a href="{{ url('/akce') }}" class="block px-3 py-1.5 text-sm text-gray-600 rounded {{ $jeKatalog ? 'active' : '' }}">Katalog akcí</a>
                    <a href="{{ url('/akce?moje_rezervovane=1') }}" class="block px-3 py-1.5 text-sm text-gray-600 rounded {{ $jeMojeRez ? 'active' : '' }}">Moje rezervované</a>
                    <a href="{{ url('/mapa') }}" class="block px-3 py-1.5 text-sm text-gray-600 rounded {{ $jeMapa ? 'active' : '' }}">Mapa</a>
                </div>
            </div>

            {{-- ADMIN --}}
            @if($navIsAdmin)
                <div class="rj-sidebar-section rounded-lg border border-primary bg-primary/5 ring-1 ring-primary p-1">
                    <div class="px-2 py-1.5 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Administrace</div>
                    <div class="rj-sidebar-section-body" style="max-height: 20rem;">
                        <a href="{{ route('admin.scraping.index') }}" class="block px-3 py-1.5 text-sm text-gray-600 rounded {{ request()->routeIs('admin.scraping.*') ? 'active' : '' }}">Scraping zdrojů</a>
                        <a href="{{ route('admin.uzivatele') }}" class="block px-3 py-1.5 text-sm text-gray-600 rounded {{ request()->routeIs('admin.uzivatele') ? 'active' : '' }}">Uživatelé a pozvánky</a>
                        <a href="{{ route('admin.error-logy.index') }}" class="block px-3 py-1.5 text-sm text-gray-600 rounded {{ request()->routeIs('admin.error-logy.*') ? 'active' : '' }}">Error logy</a>
                    </div>
                </div>
            @endif

            {{-- NASTAVENÍ --}}
            <div class="rj-sidebar-section rounded-lg border border-primary bg-primary/5 ring-1 ring-primary p-1">
                <div class="px-2 py-1.5 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Účet</div>
                <div class="rj-sidebar-section-body" style="max-height: 20rem;">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block w-full text-left px-3 py-1.5 text-sm text-gray-600 rounded hover:text-primary">Odhlásit</button>
                    </form>
                </div>
            </div>

        </nav>
    </aside>

    <script>
        function toggleSidebar() {
            var sb = document.getElementById('rj-sidebar');
            sb.classList.toggle('collapsed');
            sessionStorage.setItem('rj_sidebar', sb.classList.contains('collapsed') ? '0' : '1');
        }
        (function() {
            if (sessionStorage.getItem('rj_sidebar') === '0') {
                document.getElementById('rj-sidebar').classList.add('collapsed');
            }
        })();
    </script>
@endauth
