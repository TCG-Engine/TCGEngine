<?php
// PlayerReportLib.php — Petranaki's player reports, the Report half of Report/Block Player
// (docs/superpowers/specs/2026-10-27-saved-chat-and-player-reports-design.md). A sibling of LocalBugReportLib.php, built
// the same way so a report reliably lands where a moderator will find it:
//   * DISK FIRST. A report is written to a temp dir and renamed to SWUSim/PlayerReports/R-<n>/; the database row
//     comes after, and a database failure only delays it (PlayerReportReconcile).
//   * The number comes from a flock'd counter file; the client's one-time token makes a retry the same report.
//   * ONE REPORT PER REPORTER PER MATCH. Reporting the same player again in that match adds a follow-up (its note and
//     a fresh chat copy) and reopens the report — abuse that continues after a mid-game report still reaches the
//     moderator — up to PLAYER_REPORT_MAX_FOLLOWUPS.
//   * THE CHAT IS FROZEN INTO THE REPORT (chat.json, chat-f<k>.json): every conversation the game used, whispers in
//     full, each row with its 'src' tag. Cleaning up a game folder later never removes a report's evidence.
// Layout: ReportCounter.txt · R-<n>/report.json · R-<n>/chat.json · R-<n>/chat-f<k>.json · tokens/<sha1(token)> ·
// bymatch/<sha1(reporterId|matchId)>. Never web-served (.htaccess 404s PlayerReports/). Read only by moderators
// (zzPlayerReportViewer.php). Callers: SWUSim/SubmitPlayerReport.php, zzPlayerReportViewer.php.

require_once __DIR__ . '/Core/NetworkingLibraries.php';   // saved chat (Core/ChatStore.php) and ChatScopeKey

if (!defined('PLAYER_REPORT_TABLE'))          define('PLAYER_REPORT_TABLE', 'swusim_player_reports');
if (!defined('PLAYER_REPORT_MAX_FOLLOWUPS'))  define('PLAYER_REPORT_MAX_FOLLOWUPS', 3);
if (!defined('PLAYER_REPORT_WINDOW'))         define('PLAYER_REPORT_WINDOW', 3600);
if (!defined('PLAYER_REPORT_MAX_PER_REPORTER')) define('PLAYER_REPORT_MAX_PER_REPORTER', 10);
if (!defined('PLAYER_REPORT_MAX_PER_IP'))     define('PLAYER_REPORT_MAX_PER_IP', 20);

function PlayerReportStorageDir(): string {
    $dir = getenv('SWUSIM_PLAYERREPORT_DIR');
    return ($dir !== false && $dir !== '') ? rtrim($dir, '/') : __DIR__ . '/SWUSim/PlayerReports';
}

function _PlayerReportHasTable(?mysqli $conn): bool {
    if ($conn === null) return false;
    try {
        $stmt = $conn->prepare('SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1');
        $t = PLAYER_REPORT_TABLE;
        $stmt->bind_param('s', $t);
        $stmt->execute();
        $has = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $has;
    } catch (Throwable $e) {
        return false;
    }
}

// The conversations a report freezes: the game's own and its match's, resolved (a game inside a room or match writes
// into that shared conversation, so both usually resolve to one key). Unique, in that order.
function PlayerReportChatKeys(string $gameName, string $matchId): array {
    $keys = [];
    foreach ([$gameName, $matchId !== '' ? 'm:' . $matchId : ''] as $t) {
        if ($t === '') continue;
        $k = ChatScopeKey($t);
        if ($k !== null && !in_array($k, $keys, true)) $keys[] = $k;
    }
    return $keys;
}

// ['<key>' => rows] — the saved history; a conversation saved before saved chat existed falls back to APCu's rows.
function PlayerReportFreezeChat(array $keys): array {
    $out = [];
    foreach ($keys as $k) {
        $rows = ChatSavedHistory($k);
        if (empty($rows) && function_exists('apcu_fetch')) {
            $live = apcu_fetch('chat_' . $k);
            if (is_array($live)) $rows = array_values($live);
        }
        $out[$k] = $rows;
    }
    return $out;
}

function _PlayerReportJson($v): string {
    return (string)json_encode($v, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
}

function _PlayerReportWrite(string $path, string $data): bool {
    return $data !== '' && @file_put_contents($path, $data) === strlen($data);
}

function _PlayerReportMatchFile(int $reporterId, string $matchId): string {
    return PlayerReportStorageDir() . '/bymatch/' . sha1($reporterId . '|' . $matchId);
}

function _PlayerReportNumberAt(string $file, string $prefix = 'R-'): ?int {
    if (!is_file($file)) return null;
    $n = (int)trim((string)@file_get_contents($file));
    return ($n > 0 && is_dir(PlayerReportStorageDir() . "/R-$n")) ? $n : null;
}

// File a report, or add a follow-up to the reporter's report for this match.
// $r: gameName, matchId, lobbyId, format, reporterId, reporterName, reporterSeat, reportedId, reportedName,
// reportedSeat, note, clientIp, createdAt.
// Returns ['ok', 'reportNumber', 'kind' => 'new'|'followup'|'duplicate'|'limit', 'dbSaved', 'error'].
function PlayerReportFile(array $r, string $clientToken, ?mysqli $conn): array {
    $fail = fn(string $e) => ['ok' => false, 'reportNumber' => 0, 'kind' => '', 'dbSaved' => false, 'error' => $e];
    $dir = PlayerReportStorageDir();
    foreach (['tokens', 'bymatch'] as $sub) {
        if (!is_dir("$dir/$sub") && !@mkdir("$dir/$sub", 0775, true) && !is_dir("$dir/$sub")) return $fail('cannot create the report store');
    }
    foreach (['gameName', 'matchId', 'lobbyId', 'format', 'reporterName', 'reportedName', 'note', 'clientIp'] as $k) {
        $r[$k] = mb_scrub(strval($r[$k] ?? ''), 'UTF-8');
    }
    $now = (int)($r['createdAt'] ?? time());
    $tokenFile = "$dir/tokens/" . sha1($clientToken);
    $matchFile = _PlayerReportMatchFile((int)$r['reporterId'], $r['matchId'] !== '' ? $r['matchId'] : 'game:' . $r['gameName']);

    $fh = @fopen("$dir/ReportCounter.txt", 'c+');
    if ($fh === false || !flock($fh, LOCK_EX)) return $fail('cannot lock the report counter');
    try {
        if (($known = _PlayerReportNumberAt($tokenFile)) !== null) {
            return ['ok' => true, 'reportNumber' => $known, 'kind' => 'duplicate', 'dbSaved' => false, 'error' => null];
        }
        $chat = PlayerReportFreezeChat(PlayerReportChatKeys($r['gameName'], $r['matchId']));

        // ── a follow-up on this reporter's report for this match ──
        if (($n = _PlayerReportNumberAt($matchFile)) !== null) {
            $j = json_decode((string)@file_get_contents("$dir/R-$n/report.json"), true);
            if (!is_array($j)) return $fail("R-$n cannot be read");
            $followups = is_array($j['followups'] ?? null) ? $j['followups'] : [];
            if (count($followups) >= PLAYER_REPORT_MAX_FOLLOWUPS) {
                return ['ok' => true, 'reportNumber' => $n, 'kind' => 'limit', 'dbSaved' => false, 'error' => null];
            }
            $k = count($followups) + 1;
            $followups[] = ['at' => gmdate('c', $now), 'note' => $r['note'], 'game_name' => $r['gameName']];
            $j['followups'] = $followups;
            $j['status'] = 'open';
            $j['updated_at'] = gmdate('c', $now);
            if (!_PlayerReportWrite("$dir/R-$n/chat-f$k.json", _PlayerReportJson($chat))
                || !_PlayerReportWrite("$dir/R-$n/report.json", _PlayerReportJson($j))) {
                return $fail("cannot write the follow-up to R-$n");
            }
            @file_put_contents($tokenFile, (string)$n);
            return ['ok' => true, 'reportNumber' => $n, 'kind' => 'followup', 'dbSaved' => _PlayerReportFollowupRow($conn, $n, $k, $now), 'error' => null];
        }

        // ── a new report ──
        $n = max(1, (int)trim((string)stream_get_contents($fh, -1, 0)));
        ftruncate($fh, 0);
        rewind($fh);
        fwrite($fh, (string)($n + 1));
        fflush($fh);
        $j = [
            'report_number' => "R-$n", 'number' => $n, 'root' => 'SWUSim',
            'created_at' => gmdate('c', $now), 'updated_at' => gmdate('c', $now),
            'game_name' => $r['gameName'], 'match_id' => $r['matchId'], 'lobby_id' => $r['lobbyId'], 'format' => $r['format'],
            'reporter_id' => (int)$r['reporterId'], 'reporter_name' => $r['reporterName'], 'reporter_seat' => (int)($r['reporterSeat'] ?? 0),
            'reported_id' => (int)$r['reportedId'], 'reported_name' => $r['reportedName'], 'reported_seat' => (int)($r['reportedSeat'] ?? 0),
            'note' => $r['note'], 'status' => 'open', 'resolution_note' => '', 'followups' => [],
        ];
        $tmp = "$dir/.tmp-" . bin2hex(random_bytes(8));
        $written = @mkdir($tmp, 0775)
            && _PlayerReportWrite("$tmp/report.json", _PlayerReportJson($j))
            && _PlayerReportWrite("$tmp/chat.json", _PlayerReportJson($chat))
            && @rename($tmp, "$dir/R-$n");
        if (!$written) {
            if (is_dir($tmp)) { foreach (glob("$tmp/*") ?: [] as $f) @unlink($f); @rmdir($tmp); }
            return $fail('cannot write the report to disk');
        }
        @file_put_contents($tokenFile, (string)$n);
        @file_put_contents($matchFile, (string)$n);
    } finally {
        flock($fh, LOCK_UN);
        fclose($fh);
    }
    return ['ok' => true, 'reportNumber' => $n, 'kind' => 'new',
            'dbSaved' => _PlayerReportInsert($conn, $j, sha1($clientToken), $r['clientIp']), 'error' => null];
}

// The database row for a report.json. Never throws: the report is already safe on disk.
function _PlayerReportInsert(?mysqli $conn, array $j, string $tokenSha1, string $clientIp): bool {
    if (!_PlayerReportHasTable($conn)) return false;
    try {
        $stmt = $conn->prepare('INSERT INTO ' . PLAYER_REPORT_TABLE . '
            (reportNumber, createdAt, updatedAt, reporterId, reporterName, reporterSeat, reportedId, reportedName, reportedSeat,
             gameName, matchId, lobbyId, format, note, followups, status, resolutionNote, clientToken, clientIp)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE reportNumber = reportNumber');
        $created = (int)strtotime($j['created_at']);
        $updated = (int)strtotime($j['updated_at'] ?? $j['created_at']);
        $followups = count($j['followups'] ?? []);
        $status = strval($j['status'] ?? 'open');
        $resolution = strval($j['resolution_note'] ?? '');
        $stmt->bind_param('iiiisiisisssssissss', $j['number'], $created, $updated, $j['reporter_id'], $j['reporter_name'],
            $j['reporter_seat'], $j['reported_id'], $j['reported_name'], $j['reported_seat'], $j['game_name'], $j['match_id'],
            $j['lobby_id'], $j['format'], $j['note'], $followups, $status, $resolution, $tokenSha1, $clientIp);
        $stmt->execute();
        $stmt->close();
        return true;
    } catch (Throwable $e) {
        error_log('PlayerReport: database insert failed for R-' . $j['number'] . ': ' . $e->getMessage());
        return false;
    }
}

function _PlayerReportFollowupRow(?mysqli $conn, int $n, int $followups, int $now): bool {
    if (!_PlayerReportHasTable($conn)) return false;
    try {
        $stmt = $conn->prepare('UPDATE ' . PLAYER_REPORT_TABLE . " SET followups = ?, status = 'open', updatedAt = ? WHERE reportNumber = ?");
        $stmt->bind_param('iii', $followups, $now, $n);
        $stmt->execute();
        $ok = $stmt->affected_rows >= 1;   // 0 = no row yet (reconcile inserts it, follow-ups included)
        $stmt->close();
        return $ok;
    } catch (Throwable $e) {
        error_log("PlayerReport: follow-up update failed for R-$n: " . $e->getMessage());
        return false;
    }
}

// Seconds the caller must wait, or null. A missing table/connection never limits (never refuse a report).
function PlayerReportRateLimit(?mysqli $conn, int $reporterId, string $clientIp, int $now): ?int {
    if (!_PlayerReportHasTable($conn)) return null;
    try {
        $since = $now - PLAYER_REPORT_WINDOW;
        foreach ([['reporterId', 'i', $reporterId, PLAYER_REPORT_MAX_PER_REPORTER], ['clientIp', 's', $clientIp, PLAYER_REPORT_MAX_PER_IP]] as [$col, $type, $val, $max]) {
            $stmt = $conn->prepare('SELECT createdAt FROM ' . PLAYER_REPORT_TABLE . " WHERE $col = ? AND createdAt > ? ORDER BY createdAt DESC LIMIT $max");
            $stmt->bind_param($type . 'i', $val, $since);
            $stmt->execute();
            $times = array_map(fn($row) => (int)$row[0], $stmt->get_result()->fetch_all());
            $stmt->close();
            if (count($times) >= $max) return max(1, min($times) + PLAYER_REPORT_WINDOW - $now);
        }
        return null;
    } catch (Throwable $e) {
        return null;
    }
}

// Brings the table up to date with the disk: inserts every R-<n> the database lacks (filed while it was down), and
// re-applies a follow-up the database missed — report.json's updated_at newer than the row's updatedAt means a
// follow-up landed on disk only, so the row takes its follow-up count and its reopened status. A moderator's
// close/reopen stamps the ROW's updatedAt (not report.json's), so a later reconcile never undoes it.
// Returns the number of rows inserted or updated.
function PlayerReportReconcile(mysqli $conn): int {
    if (!_PlayerReportHasTable($conn)) return 0;
    $have = [];
    foreach ($conn->query('SELECT reportNumber, updatedAt FROM ' . PLAYER_REPORT_TABLE)->fetch_all() as $row) $have[(int)$row[0]] = (int)$row[1];
    $changed = 0;
    foreach (glob(PlayerReportStorageDir() . '/R-*', GLOB_ONLYDIR) ?: [] as $d) {
        $n = (int)substr(basename($d), 2);
        if ($n <= 0) continue;
        $j = json_decode((string)@file_get_contents("$d/report.json"), true);
        if (!is_array($j) || (int)($j['number'] ?? 0) !== $n) continue;
        if (!isset($have[$n])) {
            if (_PlayerReportInsert($conn, $j, sha1("reconciled-R-$n"), '')) $changed++;
            continue;
        }
        $diskUpdated = (int)strtotime(strval($j['updated_at'] ?? ''));
        if ($diskUpdated > $have[$n]) {
            $followups = count((array)($j['followups'] ?? []));
            $status = strval($j['status'] ?? 'open');
            $stmt = $conn->prepare('UPDATE ' . PLAYER_REPORT_TABLE . ' SET followups = ?, status = ?, updatedAt = ? WHERE reportNumber = ?');
            $stmt->bind_param('isii', $followups, $status, $diskUpdated, $n);
            $stmt->execute();
            if ($stmt->affected_rows >= 1) $changed++;
            $stmt->close();
        }
    }
    return $changed;
}

// Rows for the viewer: open reports first, then newest.
function PlayerReportList(mysqli $conn): array {
    if (!_PlayerReportHasTable($conn)) return [];
    $out = [];
    $res = $conn->query('SELECT * FROM ' . PLAYER_REPORT_TABLE . " ORDER BY (status = 'open') DESC, createdAt DESC, reportNumber DESC");
    while ($row = $res->fetch_assoc()) {
        $row['number'] = (int)$row['reportNumber'];
        $row['id'] = 'R-' . $row['number'];
        $out[] = $row;
    }
    return $out;
}

// A report's folder as the viewer reads it, or null: ['report' => report.json, 'chat' => chat.json,
// 'followupChats' => [k => chat-f<k>.json]].
function PlayerReportLoad(int $n): ?array {
    $dir = PlayerReportStorageDir() . "/R-$n";
    $j = json_decode((string)@file_get_contents("$dir/report.json"), true);
    if (!is_array($j)) return null;
    $read = fn($f) => is_file($f) ? (json_decode((string)file_get_contents($f), true) ?: []) : [];
    $followupChats = [];
    for ($k = 1; $k <= count($j['followups'] ?? []); $k++) $followupChats[$k] = $read("$dir/chat-f$k.json");
    return ['report' => $j, 'chat' => $read("$dir/chat.json"), 'followupChats' => $followupChats];
}

// What was said after the latest frozen copy: ['<key>' => saved rows with a higher id].
function PlayerReportChatAfter(array $loaded): array {
    $latest = !empty($loaded['followupChats']) ? end($loaded['followupChats']) : ($loaded['chat'] ?? []);
    $out = [];
    foreach ((array)$latest as $key => $rows) {
        $last = 0;
        foreach ((array)$rows as $row) $last = max($last, (int)($row['id'] ?? 0));
        $after = array_values(array_filter(ChatSavedHistory((string)$key), fn($row) => (int)($row['id'] ?? 0) > $last));
        if (!empty($after)) $out[$key] = $after;
    }
    return $out;
}

// $status 'closed' (with a resolution note) or 'open'. The disk copy is updated too, so a reconcile keeps it.
function PlayerReportSetStatus(mysqli $conn, int $n, string $status, string $note): array {
    if (!in_array($status, ['open', 'closed'], true)) return ['error' => 'Unknown status.'];
    if (!_PlayerReportHasTable($conn)) return ['error' => 'The player report table is missing (migration 21).'];
    $note = mb_substr(mb_scrub($note, 'UTF-8'), 0, 1000);
    $now = time();
    $stmt = $conn->prepare('UPDATE ' . PLAYER_REPORT_TABLE . ' SET status = ?, resolutionNote = ?, updatedAt = ? WHERE reportNumber = ?');
    $stmt->bind_param('ssii', $status, $note, $now, $n);
    $stmt->execute();
    $stmt->close();
    if ($conn->query('SELECT 1 FROM ' . PLAYER_REPORT_TABLE . ' WHERE reportNumber = ' . (int)$n)->num_rows < 1) return ['error' => "Player report R-$n not found."];
    $path = PlayerReportStorageDir() . "/R-$n/report.json";
    $j = json_decode((string)@file_get_contents($path), true);
    if (is_array($j)) { $j['status'] = $status; $j['resolution_note'] = $note; @file_put_contents($path, _PlayerReportJson($j)); }
    return ['success' => true, 'id' => "R-$n", 'message' => "Player report R-$n " . ($status === 'closed' ? 'closed.' : 'reopened.')];
}

function PlayerReportDelete(mysqli $conn, int $n): array {
    $root = PlayerReportStorageDir();
    $dir = "$root/R-$n";
    $inDb = _PlayerReportHasTable($conn) && $conn->query('SELECT 1 FROM ' . PLAYER_REPORT_TABLE . ' WHERE reportNumber = ' . (int)$n)->num_rows > 0;
    if (!is_dir($dir) && !$inDb) return ['error' => "Player report R-$n not found."];
    foreach (array_merge(glob("$root/tokens/*") ?: [], glob("$root/bymatch/*") ?: []) as $f) {
        if ((int)trim((string)file_get_contents($f)) === $n) @unlink($f);
    }
    if (is_dir($dir)) { foreach (glob("$dir/*") ?: [] as $f) @unlink($f); @rmdir($dir); }
    if ($inDb) $conn->query('DELETE FROM ' . PLAYER_REPORT_TABLE . ' WHERE reportNumber = ' . (int)$n);
    return ['success' => true, 'id' => "R-$n", 'message' => "Player report R-$n deleted."];
}

function PlayerReportParseNumber(string $s): ?int {
    return preg_match('/^R-([1-9][0-9]{0,8})$/', trim($s), $m) ? (int)$m[1] : null;
}
