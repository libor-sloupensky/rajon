<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Prohlížení Laravel error logů z prohlížeče.
 *
 * Logy v storage/logs/laravel-YYYY-MM-DD.log (daily rotation).
 * Default retention 14 dní (Monolog).
 */
class ErrorLogController extends Controller
{
    protected const TAIL_BYTES = 200_000;   // posledních 200 KB v UI
    protected const DOWNLOAD_MAX_BYTES = 50_000_000; // 50 MB safety limit pro download

    /** Seznam log souborů. */
    public function index()
    {
        $logy = $this->seznamLogu();
        return view('admin.error-logy.index', compact('logy'));
    }

    /** Detail jednoho logu — posledních N kB. */
    public function show(Request $request, string $soubor)
    {
        $cesta = $this->bezpecnaCesta($soubor);
        if (!$cesta) abort(404);

        $velikost = filesize($cesta);
        $tailBytes = (int) $request->input('bytes', self::TAIL_BYTES);
        $tailBytes = min($tailBytes, $velikost);

        $obsah = $this->ziskejTail($cesta, $tailBytes);

        return view('admin.error-logy.show', [
            'soubor' => $soubor,
            'velikost' => $velikost,
            'obsah' => $obsah,
            'tail_bytes' => $tailBytes,
        ]);
    }

    /** Download celého logu. */
    public function download(string $soubor): StreamedResponse
    {
        $cesta = $this->bezpecnaCesta($soubor);
        if (!$cesta) abort(404);

        if (filesize($cesta) > self::DOWNLOAD_MAX_BYTES) {
            abort(413, 'Soubor je příliš velký pro download.');
        }

        return response()->streamDownload(
            fn () => readfile($cesta),
            $soubor,
            ['Content-Type' => 'text/plain; charset=utf-8']
        );
    }

    /** Plain text endpoint — pro vykrešení do AI / curl. */
    public function raw(Request $request, string $soubor): \Illuminate\Http\Response
    {
        $cesta = $this->bezpecnaCesta($soubor);
        if (!$cesta) abort(404);

        $tailBytes = (int) $request->input('bytes', self::TAIL_BYTES);
        $obsah = $this->ziskejTail($cesta, min($tailBytes, filesize($cesta)));

        return response($obsah, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    /** Smazat log soubor. */
    public function destroy(string $soubor)
    {
        $cesta = $this->bezpecnaCesta($soubor);
        if (!$cesta) abort(404);

        @unlink($cesta);
        return back()->with('success', "Log {$soubor} smazán.");
    }

    /** Seznam log souborů s metadaty. */
    protected function seznamLogu(): array
    {
        $cesta = storage_path('logs');
        if (!is_dir($cesta)) return [];

        $soubory = glob($cesta . '/*.log') ?: [];
        rsort($soubory);

        return array_map(function ($s) {
            return [
                'nazev' => basename($s),
                'velikost' => filesize($s),
                'upraveno' => \Carbon\Carbon::createFromTimestamp(filemtime($s)),
            ];
        }, $soubory);
    }

    /** Bezpečná validace cesty (path traversal protection). */
    protected function bezpecnaCesta(string $soubor): ?string
    {
        // Jen .log soubory ve storage/logs, žádné lomítko v názvu
        if (!preg_match('/^[\w\-\.]+\.log$/i', $soubor)) {
            return null;
        }

        $cesta = storage_path('logs/' . $soubor);
        $real = realpath($cesta);
        if (!$real || !str_starts_with($real, realpath(storage_path('logs')))) {
            return null;
        }
        return $real;
    }

    /** Vrátí posledních N bytů ze souboru. */
    protected function ziskejTail(string $cesta, int $bytes): string
    {
        if ($bytes <= 0) return '';

        $velikost = filesize($cesta);
        $f = fopen($cesta, 'rb');
        if (!$f) return '';

        if ($velikost > $bytes) {
            fseek($f, $velikost - $bytes);
            // Posun na začátek řádku — vyhodit useknutý fragment
            fgets($f);
        }
        $obsah = stream_get_contents($f);
        fclose($f);
        return (string) $obsah;
    }
}
