<?php
// LocalBugReportLib.php — Petranaki's own SWUSim bug reports, stored on this box
// (docs/superpowers/specs/2026-10-22-petranaki-local-bug-reports-design.md).
//
// The owner's top requirement is that a submitted report reliably lands where the owner will find it, so:
//   * DISK FIRST. A report is written to a temp dir and renamed to SWUSim/BugReports/P-<n>/ before anything else;
//     the database row comes after, and a database failure only delays it (LocalBugReportReconcile).
//   * The number is taken from a flock'd counter file, so the player's receipt exists even with the database down.
//   * The client sends a one-time token; a retry that reaches the server twice gets the same P-<n> back.
// Layout: ReportCounter.txt · P-<n>/report.json · P-<n>/Gamestate.txt (verified only) · tokens/<sha1(token)>.
// The directory is never web-served (.htaccess 404s BugReports/, like Games/). Callers: APIs/SubmitBugReport.php,
// zzBugReportViewer.php, SWUSim/DevTools/import-bugreport.php.

require_once __DIR__ . '/Core/GameAuth.php';
require_once __DIR__ . '/Core/HTTPLibraries.php';
require_once __DIR__ . '/Core/ViewerIdentity.php';
require_once __DIR__ . '/Core/RegressionTestFramework.php';

define('LOCAL_BUG_REPORT_VERIFIED_WINDOW', 30);     // one verified report per game seat per 30 s
define('LOCAL_BUG_REPORT_UNVERIFIED_WINDOW', 3600); // unverified: 5 per client IP per hour
define('LOCAL_BUG_REPORT_UNVERIFIED_MAX', 5);
// Every report, verified or not, per client IP per hour: a spectator's report on a public game is "verified" (with
// a snapshot of up to a few MB), and one client could otherwise cycle through every live game's number.
define('LOCAL_BUG_REPORT_IP_WINDOW', 3600);
define('LOCAL_BUG_REPORT_IP_MAX', 20);

function LocalBugReportStorageDir(): string {
    $dir = getenv('SWUSIM_BUGREPORT_DIR');
    return ($dir !== false && $dir !== '') ? rtrim($dir, '/') : __DIR__ . '/SWUSim/BugReports';
}

function _LocalBugReportTokenFile(string $clientToken): string {
    return LocalBugReportStorageDir() . '/tokens/' . sha1($clientToken);
}

// Asked of THIS connection's database. Not DBTableExists(): its static cache is keyed by table name alone, so one
// connection's answer would be reused for another database.
function _LocalBugReportHasTable(?mysqli $conn): bool {
    if ($conn === null) return false;
    try {
        $res = $conn->query("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'swusim_bug_reports' LIMIT 1");
        return $res !== false && $res->num_rows > 0;
    } catch (Throwable $e) {
        return false;
    }
}

// Is this report "verified" — the game exists and the seat key is accepted — so its snapshot may be kept?
// An unverified report is still saved, without a snapshot: an unchecked request must not pull hidden state.
// Returns ['verified' => bool, 'reason' => '' | 'game not found' | 'game could not be read' |
//          'seat key not recognised' | 'no gamestate captured', 'gamestateText' => string ('' unless verified)].
function LocalBugReportVerifySeat(string $gameName, array $viewerInfo, string $authKey): array {
    $out = ['verified' => false, 'reason' => '', 'gamestateText' => ''];
    if (!IsGameNameValid($gameName) || !SimGameExists('SWUSim', $gameName)) {
        $out['reason'] = 'game not found';
        return $out;
    }
    try {
        include_once __DIR__ . '/SWUSim/ZoneClasses.php';
        include_once __DIR__ . '/SWUSim/ZoneAccessors.php';
        include_once __DIR__ . '/SWUSim/GamestateParser.php';
        $GLOBALS['gameName'] = $gameName;
        ParseGamestate(__DIR__ . '/SWUSim/');
    } catch (Throwable $e) {
        $out['reason'] = 'game could not be read';
        return $out;
    }
    if (!SimGameValidateViewerAuth('SWUSim', $gameName, $viewerInfo, $authKey)) {
        $out['reason'] = 'seat key not recognised';
        return $out;
    }
    $text = RegressionCurrentGamestateText('SWUSim', $gameName);
    if (!is_string($text) || $text === '') {
        $out['reason'] = 'no gamestate captured';
        return $out;
    }
    return ['verified' => true, 'reason' => '', 'gamestateText' => $text];
}

function LocalBugReportFindByToken(string $clientToken): ?int {
    $f = _LocalBugReportTokenFile($clientToken);
    if (!is_file($f)) return null;
    $n = (int)trim((string)@file_get_contents($f));
    return ($n > 0 && is_dir(LocalBugReportStorageDir() . "/P-$n")) ? $n : null;
}

// $report: gameName, playerID, verified, unverifiedReason, reporter, accountId, accountName, description,
// gamestateHash, clientIp, createdAt. $gamestateText is kept only for a verified report.
function LocalBugReportSave(array $report, ?string $gamestateText, string $clientToken, ?mysqli $conn): array {
    $out = ['ok' => false, 'reportNumber' => 0, 'duplicate' => false, 'dbSaved' => false, 'error' => null];
    $dir = LocalBugReportStorageDir();
    if (!is_dir("$dir/tokens") && !@mkdir("$dir/tokens", 0775, true) && !is_dir("$dir/tokens")) {
        $out['error'] = 'cannot create the report store';
        return $out;
    }
    $counterFile = "$dir/ReportCounter.txt";
    $fh = @fopen($counterFile, 'c+');
    if ($fh === false || !flock($fh, LOCK_EX)) {
        $out['error'] = 'cannot lock the report counter';
        return $out;
    }
    try {
        $known = LocalBugReportFindByToken($clientToken);
        if ($known !== null) {
            return ['ok' => true, 'reportNumber' => $known, 'duplicate' => true, 'dbSaved' => false, 'error' => null];
        }
        $n = max(1, (int)trim((string)stream_get_contents($fh, -1, 0)));
        ftruncate($fh, 0);
        rewind($fh);
        fwrite($fh, (string)($n + 1));
        fflush($fh);

        // A description cut mid-character must not make json_encode fail (report.json would be written EMPTY behind
        // a "saved" receipt) nor the database reject the row: scrub every string to valid UTF-8 first.
        foreach (['gameName', 'playerID', 'unverifiedReason', 'reporter', 'accountName', 'description', 'gamestateHash'] as $k) {
            if (isset($report[$k]) && is_string($report[$k])) $report[$k] = mb_scrub($report[$k], 'UTF-8');
        }
        $verified = !empty($report['verified']);
        $hasSnapshot = $verified && is_string($gamestateText) && $gamestateText !== '';
        $json = [
            'report_number' => "P-$n",
            'number' => $n,
            'root' => 'SWUSim',
            'created_at' => gmdate('c', (int)$report['createdAt']),
            'game_name' => (string)$report['gameName'],
            'player_id' => (string)$report['playerID'],
            'verified' => $verified,
            'unverified_reason' => (string)($report['unverifiedReason'] ?? ''),
            'reporter' => (string)($report['reporter'] ?? ''),
            'account_id' => isset($report['accountId']) ? (int)$report['accountId'] : null,
            'account_name' => isset($report['accountName']) ? (string)$report['accountName'] : null,
            'description' => (string)$report['description'],
            'gamestate_hash' => (string)($report['gamestateHash'] ?? ''),
            'has_snapshot' => $hasSnapshot,
        ];
        $tmp = "$dir/.tmp-" . bin2hex(random_bytes(8));
        $reportJson = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        // A short write counts as a failure: 0 bytes would otherwise pass a !== false check.
        $written = is_string($reportJson) && @mkdir($tmp, 0775)
            && file_put_contents("$tmp/report.json", $reportJson) === strlen($reportJson)
            && (!$hasSnapshot || file_put_contents("$tmp/Gamestate.txt", $gamestateText) === strlen($gamestateText))
            && @rename($tmp, "$dir/P-$n");
        if (!$written) {
            if (is_dir($tmp)) { foreach (glob("$tmp/*") ?: [] as $f) @unlink($f); @rmdir($tmp); }
            $out['error'] = 'cannot write the report to disk';
            return $out;
        }
        file_put_contents(_LocalBugReportTokenFile($clientToken), (string)$n);
    } finally {
        flock($fh, LOCK_UN);
        fclose($fh);
    }

    $out = ['ok' => true, 'reportNumber' => $n, 'duplicate' => false, 'dbSaved' => false, 'error' => null];
    $out['dbSaved'] = _LocalBugReportInsert($conn, $json, sha1($clientToken), (string)($report['clientIp'] ?? ''), (int)$report['createdAt']);
    return $out;
}

// The database row for a report.json. Never throws: the report is already safe on disk.
function _LocalBugReportInsert(?mysqli $conn, array $j, string $tokenSha1, string $clientIp, int $createdAt): bool {
    if (!_LocalBugReportHasTable($conn)) return false;
    try {
        $stmt = $conn->prepare('INSERT INTO swusim_bug_reports
            (reportNumber, createdAt, gameName, playerID, verified, unverifiedReason, reporter, accountId, accountName,
             description, gamestateHash, hasSnapshot, clientToken, clientIp)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE reportNumber = reportNumber');
        $verified = $j['verified'] ? 1 : 0;
        $hasSnapshot = $j['has_snapshot'] ? 1 : 0;
        $stmt->bind_param('iissississsiss', $j['number'], $createdAt, $j['game_name'], $j['player_id'], $verified,
            $j['unverified_reason'], $j['reporter'], $j['account_id'], $j['account_name'], $j['description'],
            $j['gamestate_hash'], $hasSnapshot, $tokenSha1, $clientIp);
        $stmt->execute();
        $stmt->close();
        return true;
    } catch (Throwable $e) {
        error_log('LocalBugReport: database insert failed for P-' . $j['number'] . ': ' . $e->getMessage());
        return false;
    }
}

// Seconds the caller must wait, or null. A missing table/connection never limits (never refuse a report).
function LocalBugReportRateLimit(?mysqli $conn, bool $verified, string $gameName, string $playerID, string $clientIp, int $now): ?int {
    if (!_LocalBugReportHasTable($conn)) return null;
    try {
        $since = $now - LOCAL_BUG_REPORT_IP_WINDOW;
        $stmt = $conn->prepare('SELECT createdAt FROM swusim_bug_reports
            WHERE clientIp = ? AND createdAt > ? ORDER BY createdAt DESC LIMIT ' . LOCAL_BUG_REPORT_IP_MAX);
        $stmt->bind_param('si', $clientIp, $since);
        $stmt->execute();
        $times = array_map(fn($r) => (int)$r[0], $stmt->get_result()->fetch_all());
        $stmt->close();
        if (count($times) >= LOCAL_BUG_REPORT_IP_MAX) return max(1, min($times) + LOCAL_BUG_REPORT_IP_WINDOW - $now);

        if ($verified) {
            $since = $now - LOCAL_BUG_REPORT_VERIFIED_WINDOW;
            $stmt = $conn->prepare('SELECT MAX(createdAt) FROM swusim_bug_reports
                WHERE gameName = ? AND playerID = ? AND verified = 1 AND createdAt > ?');
            $stmt->bind_param('ssi', $gameName, $playerID, $since);
            $stmt->execute();
            $latest = $stmt->get_result()->fetch_row()[0];
            $stmt->close();
            return $latest === null ? null : max(1, (int)$latest + LOCAL_BUG_REPORT_VERIFIED_WINDOW - $now);
        }
        $since = $now - LOCAL_BUG_REPORT_UNVERIFIED_WINDOW;
        $stmt = $conn->prepare('SELECT createdAt FROM swusim_bug_reports
            WHERE clientIp = ? AND verified = 0 AND createdAt > ? ORDER BY createdAt DESC LIMIT ' . LOCAL_BUG_REPORT_UNVERIFIED_MAX);
        $stmt->bind_param('si', $clientIp, $since);
        $stmt->execute();
        $times = array_map(fn($r) => (int)$r[0], $stmt->get_result()->fetch_all());
        $stmt->close();
        if (count($times) < LOCAL_BUG_REPORT_UNVERIFIED_MAX) return null;
        return max(1, min($times) + LOCAL_BUG_REPORT_UNVERIFIED_WINDOW - $now);
    } catch (Throwable $e) {
        return null;
    }
}

// Inserts a row for every P-<n> on disk that the database lacks (a report saved while the database was down).
function LocalBugReportReconcile(mysqli $conn): int {
    if (!_LocalBugReportHasTable($conn)) return 0;
    $have = [];
    foreach ($conn->query('SELECT reportNumber FROM swusim_bug_reports')->fetch_all() as $r) $have[(int)$r[0]] = true;
    $tokens = [];
    foreach (glob(LocalBugReportStorageDir() . '/tokens/*') ?: [] as $f) $tokens[(int)trim((string)file_get_contents($f))] = basename($f);
    $added = 0;
    foreach (glob(LocalBugReportStorageDir() . '/P-*', GLOB_ONLYDIR) ?: [] as $d) {
        $n = (int)substr(basename($d), 2);
        if ($n <= 0 || isset($have[$n])) continue;
        $j = json_decode((string)@file_get_contents("$d/report.json"), true);
        if (!is_array($j) || (int)($j['number'] ?? 0) !== $n) continue;
        // The token's sha1 keeps retries idempotent after reconcile; a report with no token file gets a stand-in.
        $tokenSha1 = $tokens[$n] ?? sha1("reconciled-P-$n");
        if (_LocalBugReportInsert($conn, $j, $tokenSha1, '', (int)strtotime((string)$j['created_at']))) $added++;
    }
    return $added;
}

// The viewer's warning when reports on disk can't be listed: no database connection, or migration 19 not run.
// '' when the table is there (disk-only reports are reconciled into it instead).
function LocalBugReportUnindexedNotice(?mysqli $conn): string {
    if (_LocalBugReportHasTable($conn)) return '';
    $n = count(glob(LocalBugReportStorageDir() . '/P-*', GLOB_ONLYDIR) ?: []);
    if ($n === 0) return '';
    return "$n Petranaki bug report" . ($n === 1 ? ' is' : 's are') . ' saved on disk but not in the database, so '
        . ($n === 1 ? 'it is' : 'they are') . ' not listed here. Run Database/migrations/19_swusim_bug_reports.sql on this '
        . "site's database (or check the database connection); they appear on the next load.";
}

// Rows in the viewer's shape (see BugReportViewerLib.php), newest first.
function LocalBugReportList(mysqli $conn): array {
    if (!_LocalBugReportHasTable($conn)) return [];
    $out = [];
    $res = $conn->query('SELECT * FROM swusim_bug_reports ORDER BY createdAt DESC, reportNumber DESC');
    while ($r = $res->fetch_assoc()) {
        $n = (int)$r['reportNumber'];
        $who = $r['reporter'] !== '' ? $r['reporter'] : ((string)$r['accountName'] !== '' ? (string)$r['accountName'] : 'Unknown');
        $out[] = [
            'id' => "P-$n",
            'number' => $n,
            'source' => 'local',
            'root_name' => 'SWUSim',
            'game_name' => $r['gameName'],
            'origin' => 'engine-ui',
            'discord_username' => $who,
            'status' => $r['status'],
            'verified' => (bool)$r['verified'],
            'unverified_reason' => $r['unverifiedReason'],
            'gamestate_hash' => $r['gamestateHash'],
            'has_snapshot' => (bool)$r['hasSnapshot'],
            'snapshot_format' => 'package-v1',
            'created_at' => gmdate('Y-m-d H:i:s', (int)$r['createdAt']),
            'description' => $r['description'],
        ];
    }
    return $out;
}

function LocalBugReportResolve(mysqli $conn, int $number, string $note): array {
    if (!_LocalBugReportHasTable($conn)) return ['error' => 'The bug report table is missing (migration 19).'];
    $stmt = $conn->prepare("UPDATE swusim_bug_reports SET status = 'resolved', resolutionNote = ? WHERE reportNumber = ?");
    $stmt->bind_param('si', $note, $number);
    $stmt->execute();
    $changed = $stmt->affected_rows;
    $stmt->close();
    if ($changed < 1) {
        $exists = $conn->query('SELECT 1 FROM swusim_bug_reports WHERE reportNumber = ' . (int)$number)->num_rows > 0;
        if (!$exists) return ['error' => "Bug report P-$number not found."];
    }
    return ['success' => true, 'id' => "P-$number", 'message' => "Bug report P-$number closed."];
}

function LocalBugReportDelete(mysqli $conn, int $number): array {
    $dir = LocalBugReportStorageDir() . "/P-$number";
    $inDb = _LocalBugReportHasTable($conn) && $conn->query('SELECT 1 FROM swusim_bug_reports WHERE reportNumber = ' . (int)$number)->num_rows > 0;
    if (!is_dir($dir) && !$inDb) return ['error' => "Bug report P-$number not found."];
    foreach (glob(LocalBugReportStorageDir() . '/tokens/*') ?: [] as $f) {
        if ((int)trim((string)file_get_contents($f)) === $number) @unlink($f);
    }
    if (is_dir($dir)) { foreach (glob("$dir/*") ?: [] as $f) @unlink($f); @rmdir($dir); }
    if ($inDb) $conn->query('DELETE FROM swusim_bug_reports WHERE reportNumber = ' . (int)$number);
    return ['success' => true, 'id' => "P-$number", 'message' => "Bug report P-$number deleted."];
}

// The report's package as a temp zip (report.json + Gamestate.txt), or null when it has no snapshot. The zip
// is built at download time; the caller streams it and unlinks it. Its download name is
// bugreport-P-<n>-game-<gameName>.zip (LocalBugReportZipName).
function LocalBugReportZipPath(int $number): ?string {
    $dir = LocalBugReportStorageDir() . "/P-$number";
    if (!is_file("$dir/report.json") || !is_file("$dir/Gamestate.txt")) return null;
    $tmp = tempnam(sys_get_temp_dir(), 'bugreport-');
    $zip = new ZipArchive();
    if ($tmp === false || $zip->open($tmp, ZipArchive::OVERWRITE) !== true) return null;
    $zip->addFile("$dir/report.json", 'report.json');
    $zip->addFile("$dir/Gamestate.txt", 'Gamestate.txt');
    return $zip->close() ? $tmp : null;
}

function LocalBugReportZipName(int $number): string {
    $j = json_decode((string)@file_get_contents(LocalBugReportStorageDir() . "/P-$number/report.json"), true);
    $game = preg_replace('/[^0-9A-Za-z_-]/', '', (string)($j['game_name'] ?? ''));
    return "bugreport-P-$number-game-$game.zip";
}

// Puts a downloaded package into <gamesDir>/<game_name>/ under the SAME game number as on Petranaki, so the report
// text and the local game agree (SWUSim/DevTools/import-bugreport.php). Local numbers are in the 46,000s and prod's
// start low, so an existing folder is moved aside to <game>.bak-<YmdHis> first, never overwritten. The zip must hold
// exactly report.json + Gamestate.txt — any other entry, or any path, is refused before anything is written.
// GameIDCounter.txt is never touched. Returns ['ok', 'gameName', 'backup' => ?string, 'error' => ?string].
function LocalBugReportImport(string $zipPath, string $gamesDir, int $now): array {
    $fail = fn(string $e) => ['ok' => false, 'gameName' => '', 'backup' => null, 'error' => $e];
    $zip = new ZipArchive();
    if (!is_file($zipPath) || $zip->open($zipPath, ZipArchive::RDONLY) !== true) return $fail('Not a zip file: ' . $zipPath);
    $names = [];
    for ($i = 0; $i < $zip->numFiles; $i++) $names[] = (string)$zip->getNameIndex($i);
    foreach ($names as $name) {
        if (!in_array($name, ['report.json', 'Gamestate.txt'], true)) {
            $zip->close();
            return $fail('Not a bug report package: unexpected entry "' . $name . '".');
        }
    }
    $report = in_array('report.json', $names, true) ? json_decode((string)$zip->getFromName('report.json'), true) : null;
    $game = is_array($report) ? (string)($report['game_name'] ?? '') : '';
    if ($game === '' || !ctype_digit($game)) {
        $zip->close();
        return $fail('Not a bug report package: report.json has no numeric game_name.');
    }
    if (!in_array('Gamestate.txt', $names, true)) {
        $zip->close();
        return $fail(($report['report_number'] ?? 'This report') . ' is unverified (' . ($report['unverified_reason'] ?? 'no reason') . ') — it has no snapshot to import.');
    }
    $gamestate = (string)$zip->getFromName('Gamestate.txt');
    $reportJson = (string)$zip->getFromName('report.json');
    $zip->close();

    $target = rtrim($gamesDir, '/') . '/' . $game;
    $backup = null;
    if (file_exists($target)) {
        $backup = $target . '.bak-' . date('YmdHis', $now);
        if (file_exists($backup)) return $fail('Backup folder already exists: ' . $backup);
        if (!@rename($target, $backup)) return $fail('Could not move the existing game folder aside: ' . $target);
    }
    if (!@mkdir($target, 0775) || file_put_contents("$target/Gamestate.txt", $gamestate) === false
        || file_put_contents("$target/report.json", $reportJson) === false) {
        return ['ok' => false, 'gameName' => $game, 'backup' => $backup, 'error' => 'Could not write ' . $target];
    }
    return ['ok' => true, 'gameName' => $game, 'backup' => $backup, 'error' => null];
}

// "P-42", "p-42", "42", or text containing "Bug Report P-42" (the viewer's copy text) → 42.
function LocalBugReportParseNumber(string $s): ?int {
    $s = trim($s);
    if (preg_match('/^(?:P-)?(\d+)$/i', $s, $m)) return (int)$m[1] > 0 ? (int)$m[1] : null;
    if (preg_match('/\bP-(\d+)\b/i', $s, $m)) return (int)$m[1] > 0 ? (int)$m[1] : null;
    return null;
}
