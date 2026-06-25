<?php

class TestRunner {
    public static function run(?string $filter = null, ?float $wallStart = null, bool $withDetails = false): void {
        $wallStart = $wallStart ?? microtime(true);
        $results   = [];

        $casesDir = __DIR__ . '/../Cases/';
        $files    = self::_discoverFiles($casesDir, $filter);

        foreach ($files as $file) {
            $before = get_defined_functions()['user'];
            include_once $file;
            $after = get_defined_functions()['user'];

            $newFns   = array_diff($after, $before);
            $testFns  = array_filter($newFns, fn($f) => str_starts_with($f, 'test_'));

            foreach ($testFns as $fn) {
                if ($filter !== null && stripos($fn, $filter) === false) continue;

                InitializeGamestate();
                $t = microtime(true);
                try {
                    $fn();
                    $results[] = [
                        'name'   => $fn,
                        'passed' => true,
                        'ms'     => self::_ms($t),
                    ];
                } catch (Throwable $e) {
                    $results[] = [
                        'name'     => $fn,
                        'passed'   => false,
                        'error'    => $e->getMessage(),
                        'location' => self::_location($e),
                        'ms'       => self::_ms($t),
                    ];
                }
            }
        }

        self::_render($results, microtime(true) - $wallStart, $withDetails);
    }

    private static function _discoverFiles(string $dir, ?string $filter): array {
        if (!is_dir($dir)) return [];
        $it    = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        $files = [];
        foreach ($it as $file) {
            if (!$file->isFile() || !str_ends_with($file->getFilename(), 'Test.php')) continue;
            if ($filter !== null && stripos($file->getFilename(), $filter) === false) continue;
            $files[] = $file->getPathname();
        }
        sort($files);
        return $files;
    }

    private static function _location(Throwable $e): string {
        $trace = $e->getTrace();
        // Walk up the trace to find the first frame inside Cases/
        foreach ($trace as $frame) {
            $f = $frame['file'] ?? '';
            if (str_contains($f, '/Cases/')) {
                return basename($f) . ':' . ($frame['line'] ?? '?');
            }
        }
        return basename($e->getFile()) . ':' . $e->getLine();
    }

    private static function _ms(float $start): int {
        return (int) round((microtime(true) - $start) * 1000);
    }

    // $withDetails (?withDetails=1) lists EVERY test with its run time — for debugging.
    // The default stays minimal (failures + summary only) so a plain regression curl
    // returns a small response.
    private static function _render(array $results, float $totalSec, bool $withDetails = false): void {
        $passed   = count(array_filter($results, fn($r) => $r['passed']));
        $failed   = count($results) - $passed;
        $totalMs  = (int) round($totalSec * 1000);
        $color    = $failed > 0 ? '#c0392b' : '#27ae60';

        echo '<pre style="font-family:monospace;font-size:13px;line-height:1.5;padding:24px;background:#1e1e1e;color:#d4d4d4">';
        if ($withDetails) {
            // Full listing: every test, pass and fail, with its run time.
            echo "<b>SWUSim Test Suite — " . date('Y-m-d H:i:s') . "</b>\n";
            echo str_repeat('─', 64) . "\n";
            foreach ($results as $r) {
                $mark = $r['passed']
                    ? "<span style='color:#27ae60'>✓</span>"
                    : "<span style='color:#c0392b'>✗</span>";
                printf(
                    "%s  %-54s <span style='color:#666'>(%dms)</span>\n",
                    $mark,
                    htmlspecialchars($r['name']),
                    $r['ms']
                );
                if (!$r['passed']) {
                    echo "     <span style='color:#e74c3c'>" . htmlspecialchars($r['error']) . "</span>";
                    if (!empty($r['location'])) {
                        echo "  <span style='color:#666'>[" . htmlspecialchars($r['location']) . "]</span>";
                    }
                    echo "\n";
                }
            }
            echo str_repeat('─', 64) . "\n";
        } else if ($failed > 0) {
            echo "<b>SWUSim Test Suite — " . date('Y-m-d H:i:s') . "</b>\n";
            echo str_repeat('─', 64) . "\n";
            foreach ($results as $r) {
                if ($r['passed']) continue;
                printf(
                    "<span style='color:#c0392b'>✗</span>  %-54s <span style='color:#666'>(%dms)</span>\n",
                    htmlspecialchars($r['name']),
                    $r['ms']
                );
                echo "     <span style='color:#e74c3c'>" . htmlspecialchars($r['error']) . "</span>";
                if (!empty($r['location'])) {
                    echo "  <span style='color:#666'>[" . htmlspecialchars($r['location']) . "]</span>";
                }
                echo "\n";
            }
            echo str_repeat('─', 64) . "\n";
        }
        echo "<span style='color:{$color}'><b>{$passed} passed  {$failed} failed</b></span>"
           . "  <span style='color:#666'>({$totalMs}ms total)</span>\n";
        echo '</pre>';
    }
}
