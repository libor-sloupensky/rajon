<?php

namespace App\Console\Commands;

use App\Services\ExcelImport\AkceHistoricMatcher;
use App\Services\ExcelImport\ExportCollector;
use App\Services\ExcelImport\FileImporter;
use App\Services\ExcelImport\FileSpec;
use App\Services\ExcelImport\ImportStats;
use Illuminate\Console\Command;

/**
 * Naparsuje 9 historických XLSX v zadaném adresáři a nasype je do DB:
 * `akce` (kanonická akce) + `akce_vykazy` (ročníky 2022–2025).
 *
 * Rozhodnutí a strategie — modules/historicka_data.md.
 */
class ExcelImportCommand extends Command
{
    protected $signature = 'excel:import
                            {adresar=temporary : Adresář s .xlsx soubory}
                            {--dry-run : Nic nezapíše do DB, jen vypíše co by udělal}
                            {--export= : Zapiš výsledek do JSON (seedable). Implikuje dry-run.}
                            {--soubor= : Jen jeden konkrétní soubor (název jak je v FileSpec)}
                            {--od-kroku=1 : Přeskočit prvních N-1 souborů v pořadí (restart po selhání)}';

    protected $description = 'Import historických XLSX (2022–2025) do akce + akce_vykazy';

    public function handle(): int
    {
        $adresar = base_path((string) $this->argument('adresar'));
        if (!is_dir($adresar)) {
            $this->error("Adresář neexistuje: {$adresar}");
            return self::FAILURE;
        }

        $exportPath = $this->option('export');
        $dryRun = (bool) $this->option('dry-run') || $exportPath !== null;
        $filtrSoubor = $this->option('soubor');
        $odKroku = (int) $this->option('od-kroku');

        $this->line(sprintf(
            'Excel import — adresář=%s, dry-run=%s, od-kroku=%d%s%s',
            $adresar, $dryRun ? 'ANO' : 'NE', $odKroku,
            $filtrSoubor ? ", soubor={$filtrSoubor}" : '',
            $exportPath ? ", export={$exportPath}" : '',
        ));

        $specs = FileSpec::vse($adresar);

        $stats = new ImportStats();
        $matcher = new AkceHistoricMatcher(preskocDbNacten: $dryRun);
        $collector = $exportPath !== null ? new ExportCollector() : null;
        $importer = new FileImporter(
            matcher: $matcher,
            stats: $stats,
            dryRun: $dryRun,
            log: fn (string $msg) => $this->line($msg),
            collector: $collector,
        );

        foreach ($specs as $krok => $spec) {
            $poradi = $krok + 1;
            if ($poradi < $odKroku) continue;
            if ($filtrSoubor && $spec->nazev !== $filtrSoubor) continue;

            if (!is_file($spec->cesta)) {
                $this->warn("[{$poradi}] soubor chybí: {$spec->cesta}");
                continue;
            }

            try {
                $importer->zpracuj($spec);
            } catch (\Throwable $e) {
                $this->error("[{$poradi}] {$spec->nazev} — selhalo: {$e->getMessage()}");
                $this->line($e->getTraceAsString());
                return self::FAILURE;
            }
        }

        $this->newLine();
        $this->line(sprintf(
            'Hotovo: nových akcí=%d, matched=%d, výkazů=%d%s',
            $stats->created, $stats->matched, $stats->vykazy,
            $dryRun ? ' (DRY-RUN — nic se neuložilo)' : '',
        ));

        if ($collector && $exportPath) {
            $absPath = str_starts_with($exportPath, '/') || preg_match('/^[A-Z]:\\\\/', $exportPath)
                ? $exportPath
                : base_path($exportPath);
            $dir = dirname($absPath);
            if (!is_dir($dir)) @mkdir($dir, 0755, true);
            file_put_contents($absPath, $collector->toJson());
            $this->line("Export: {$absPath} (" . count($collector->akce) . ' akcí, ' . count($collector->vykazy) . ' výkazů)');
        }

        if ($this->getOutput()->isVerbose()) {
            $this->newLine();
            $this->line('Top 30 nejčastěji matchovaných akcí (ukazuje, jak dobře dedup funguje):');
            arsort($stats->matchePerAkce);
            $i = 0;
            foreach ($stats->matchePerAkce as $klic => $pocet) {
                if ($i++ >= 30) break;
                $this->line("  {$pocet}× {$klic}");
            }
        }
        return self::SUCCESS;
    }
}
