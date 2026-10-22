<?php
// Records melee.gg tournaments into packages that swustats.net imports without the network (melee 403s
// the droplet). Driven by the swustats-melee-package skill; see Stats/MeleePackage.php for the format.
//
//   docker exec otmtcge-swudeck-web-server-1 php /var/www/html/TCGEngine/DevTools/melee-package.php <melee link> [... up to 5]
//
// Each tournament is imported into a scratch database (<site db>_meleepkg: only the three melee tables,
// recreated per tournament) so the real local tables are never touched and an already-imported
// tournament still records in full. The package is then replayed into a fresh scratch copy and must
// reproduce the same decks/matchups before it is written to .melee-packages/melee-<id>.json.gz.
// Exit code: 0 when every link produced a verified package, 1 otherwise.

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

$links = array_slice($argv, 1);
if (empty($links)) { fwrite(STDERR, "usage: php DevTools/melee-package.php <melee link> [... up to 5]\n"); exit(1); }

chdir(__DIR__ . '/../Stats'); // the importer's includes are relative to Stats/
require_once __DIR__ . '/../Database/ConnectionManager.php';
require_once __DIR__ . '/../Stats/MeleePackage.php';

if (count($links) > SWU_MELEE_PACKAGE_MAX_FILES) {
    fwrite(STDERR, 'At most ' . SWU_MELEE_PACKAGE_MAX_FILES . " links per run.\n");
    exit(1);
}

$sourceDb = ResolveDatabaseName();
$scratchDb = $sourceDb . '_meleepkg';
$tables = ['meleetournament', 'meleetournamentdeck', 'meleetournamentmatchup'];

function melee_pkg_reset_scratch(string $sourceDb, string $scratchDb, array $tables): void {
    if (substr($scratchDb, -9) !== '_meleepkg') throw new RuntimeException("Refusing to reset $scratchDb");
    $admin = GetLocalMySQLConnection();
    $admin->query("CREATE DATABASE IF NOT EXISTS `$scratchDb`");
    foreach ($tables as $t) {
        $admin->query("DROP TABLE IF EXISTS `$scratchDb`.`$t`");
        $admin->query("CREATE TABLE `$scratchDb`.`$t` LIKE `$sourceDb`.`$t`");
    }
    $admin->close();
}

// Everything below connects to the scratch database (the importer opens its own connections).
melee_pkg_reset_scratch($sourceDb, $scratchDb, $tables);
putenv("MYSQL_DATABASE_NAME=$scratchDb");
require_once __DIR__ . '/../Stats/MeleeTournamentParserAPI.php';

$outDir = __DIR__ . '/../.melee-packages';
if (!is_dir($outDir)) mkdir($outDir, 0775, true);

$failed = 0;
foreach ($links as $link) {
    $meleeId = SWUMeleeIdFromLink($link) ?? (ctype_digit($link) ? $link : null);
    if ($meleeId === null) { echo "FAIL  $link — not a melee.gg tournament link\n"; $failed++; continue; }

    putenv("MYSQL_DATABASE_NAME=$sourceDb");
    melee_pkg_reset_scratch($sourceDb, $scratchDb, $tables);
    putenv("MYSQL_DATABASE_NAME=$scratchDb");

    // 1. Record a live import.
    SWUMeleeFetchRecord();
    $reason = null; $diag = null;
    ob_start();
    $errorLog = ini_set('error_log', '/dev/null'); // the card-id lookup logs one line per leader
    try { $tid = importMeleeTournamentById($meleeId, null, $reason, $diag); }
    catch (Throwable $e) { $tid = false; $reason = $e->getMessage(); }
    ini_set('error_log', (string)$errorLog);
    ob_end_clean();
    $responses = SWUMeleeFetchRecorded();
    SWUMeleeFetchLive();
    if (!$tid) {
        $http = $diag['roundSelection']['pageHttpCode'] ?? null;
        echo "FAIL  $meleeId — " . ($reason ?: 'import failed') . ($http ? " (melee answered HTTP $http)" : '') . "\n";
        $failed++;
        continue;
    }
    $conn = GetLocalMySQLConnection();
    $summary = SWUMeleeTournamentSummary($conn, (int)$tid);
    $conn->close();
    if ($summary['decks'] === 0) { echo "FAIL  $meleeId — melee returned no decks\n"; $failed++; continue; }

    $pkg = [
        'format' => SWU_MELEE_PACKAGE_FORMAT,
        'melee_tournament_id' => $meleeId,
        'source_url' => "https://melee.gg/Tournament/View/$meleeId",
        'created_at' => gmdate('c'),
        'parser_version' => MELEE_PARSER_VERSION,
        'summary' => $summary,
        'responses' => $responses,
    ];
    $gz = gzencode(json_encode($pkg, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 9);

    // 2. Prove the package replays to the same tournament before handing it over.
    putenv("MYSQL_DATABASE_NAME=$sourceDb");
    melee_pkg_reset_scratch($sourceDb, $scratchDb, $tables);
    putenv("MYSQL_DATABASE_NAME=$scratchDb");
    $decoded = SWUMeleePackageDecode($gz, $decodeError);
    $conn = GetLocalMySQLConnection();
    $errorLog = ini_set('error_log', '/dev/null');
    $replay = $decoded ? SWUMeleePackageImport($conn, $decoded) : ['status' => 'failed', 'message' => $decodeError];
    ini_set('error_log', (string)$errorLog);
    $conn->close();
    if ($replay['status'] !== 'imported' || isset($replay['warning'])) {
        echo "FAIL  $meleeId — the package does not replay: " . ($replay['warning'] ?? $replay['message']) . "\n";
        $failed++;
        continue;
    }

    $path = "$outDir/melee-$meleeId.json.gz";
    file_put_contents("$path.tmp", $gz);
    rename("$path.tmp", $path);
    printf("OK    %s  %s — %d decks, %d matchups, %d melee answers, %s KB\n      %s\n",
        $meleeId, $summary['tournament_name'], $summary['decks'], $summary['matchups'], count($responses),
        number_format(strlen($gz) / 1024, 0), '.melee-packages/' . basename($path));
}
exit($failed ? 1 : 0);
