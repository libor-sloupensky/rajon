<footer class="mt-auto bg-white border-t border-gray-200">
    <div class="mx-auto px-8 py-6" style="max-width: 80rem;">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="text-sm text-gray-500">
                &copy; {{ date('Y') }} Rajón — katalog akcí pro WormUP franšízanty
            </div>
            <div class="flex items-center gap-4 text-sm text-gray-400">
                <a href="{{ url('/') }}" class="hover:text-primary transition">Domů</a>
                <a href="{{ url('/akce') }}" class="hover:text-primary transition">Akce</a>
                <a href="{{ url('/mapa') }}" class="hover:text-primary transition">Mapa</a>
            </div>
        </div>
    </div>
</footer>
