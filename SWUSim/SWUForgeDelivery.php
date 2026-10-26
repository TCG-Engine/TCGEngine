<?php
// Petranaki → SWU Forge game delivery. When a recorded PvP game finishes (the SWU-PGN writer's Finish.php), each seat
// whose Petranaki account is linked to SWU Forge gets a row in swuforge_deliveries holding the game's all-seeing file;
// a worker (SWUForgeDeliverWorker.php, run every minute) posts each due row to Forge's POST /api/petranaki/pgn.
// Nothing here runs inside a player's request beyond one INSERT: the HTTP call, up to 30 s, is the worker's.
//
// Forge's answers (Q&A 25 Oct 2026 §8): 200 ok → done · 200 skipped → skipped · 400/413/415 → failed (never retried) ·
// 401 → refresh once and resend, a second 401 is an unlinked player · 403 (account not enabled) / 409 (no profile) →
// the player must act: the profile says so and the game waits, hourly, for up to a week · 502/503/no answer → retried
// with exponential backoff from 30 s. Re-delivery is idempotent on Forge's side (game, profile), so a retry is safe.
require_once __DIR__ . '/SWUForgeLink.php';

const SWUFORGE_MAX_BYTES = 4 * 1024 * 1024;   // Forge refuses bigger bodies with 413
const SWUFORGE_MAX_ATTEMPTS = 12;             // 502/503/no-answer attempts before a row fails (~1.5 days of backoff)
const SWUFORGE_BACKOFF_CAP = 6 * 3600;
const SWUFORGE_HELD_WAIT = 3600;              // 403 / 409: try again hourly…
const SWUFORGE_HELD_FOR = 7 * 86400;          // …for a week, then fail

function SWUForgeDeliveryReady(mysqli $conn): bool { return DBTableExists($conn, 'swuforge_deliveries'); }

// The deck id of a swuforge.com/decks/<id> import ('' for any other deck source).
function SWUForgeDeckIdFromLink(string $deckLink): string {
    return preg_match('#swuforge\.com/decks/([A-Za-z0-9_-]{1,64})#i', $deckLink, $m) ? $m[1] : '';
}

// Queue a finished game for each linked seat. $meta is the writer's game meta (accounts, matchId); $match the game's
// Match record (deck names and links), read here when not given. Returns how many rows were queued. Never throws.
function SWUForgeEnqueueGame(string $gameName, array $meta, string $allText, int $now = 0, ?array $match = null): int {
    $now = $now ?: time();
    if (!SWUForgeConfigured()) return 0;   // no client secret on this box: SWU Forge is off — nothing is queued
    $accounts = array_filter(array_map(fn($id) => $id === null ? 0 : intval($id), (array)($meta['accounts'] ?? [])), fn($id) => $id > 0);
    if (!$accounts) return 0;
    if (strlen($allText) > SWUFORGE_MAX_BYTES) {
        error_log("SWU Forge: game $gameName not queued — its file is over 4 MB (" . strlen($allText) . ' bytes)');
        return 0;
    }
    try {
        $matchId = strval($meta['matchId'] ?? '');
        if ($matchId === '' && function_exists('SWUReadMatchRef') && is_array($ref = SWUReadMatchRef($gameName))) $matchId = strval($ref['matchId'] ?? '');
        if ($match === null && $matchId !== '' && function_exists('MatchRead')) $match = MatchRead('SWUSim', $matchId);
        $seatInfo = function (int $seat) use ($match): array {
            $p = is_array($match['players'][strval($seat)] ?? null) ? $match['players'][strval($seat)] : [];
            return ['name' => mb_substr(strval($p['originalDeck']['name'] ?? ''), 0, 255), 'link' => strval($p['deckLink'] ?? '')];
        };
        $conn = GetLocalMySQLConnection();
        try {
            if (!SWUForgeLinkReady($conn) || !SWUForgeDeliveryReady($conn)) return 0;
            $queued = 0;
            foreach ($accounts as $seat => $usersId) {
                $seat = intval($seat);
                if (($seat !== 1 && $seat !== 2) || SWUForgeGetLink($conn, $usersId) === null) continue;
                $mine = $seatInfo($seat); $theirs = $seatInfo(3 - $seat);
                $deckId = SWUForgeDeckIdFromLink($mine['link']);
                $lobbyId = mb_substr($matchId, 0, 200);
                // A game finished again (a bookmark reopened it) replaces its row and is sent afresh.
                $stmt = $conn->prepare("INSERT INTO swuforge_deliveries
                    (usersId, gameName, seat, pgn, lobbyId, deckName, opponentDeckName, deckId, status, attempts, nextAttemptAt, lastHttp, lastError, createdAt, updatedAt)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 0, ?, 0, '', ?, ?)
                    ON DUPLICATE KEY UPDATE pgn = VALUES(pgn), lobbyId = VALUES(lobbyId), deckName = VALUES(deckName),
                        opponentDeckName = VALUES(opponentDeckName), deckId = VALUES(deckId), status = 'pending', attempts = 0,
                        nextAttemptAt = VALUES(nextAttemptAt), lastHttp = 0, lastError = '', createdAt = VALUES(createdAt), updatedAt = VALUES(updatedAt)");
                $stmt->bind_param('isisssssiii', $usersId, $gameName, $seat, $allText, $lobbyId, $mine['name'], $theirs['name'], $deckId, $now, $now, $now);
                $stmt->execute();
                $stmt->close();
                $queued++;
            }
            return $queued;
        } finally {
            $conn->close();
        }
    } catch (Throwable $e) {
        error_log("SWU Forge: queueing game $gameName failed: " . $e->getMessage());
        return 0;
    }
}

function _SWUForgeSetRow(mysqli $conn, int $id, array $set): void {
    $cols = []; $types = ''; $vals = [];
    foreach ($set as $k => $v) { $cols[] = "`$k` = ?"; $types .= is_int($v) ? 'i' : 's'; $vals[] = $v; }
    $types .= 'i'; $vals[] = $id;
    $stmt = $conn->prepare('UPDATE swuforge_deliveries SET ' . implode(', ', $cols) . ' WHERE id = ?');
    $stmt->bind_param($types, ...$vals);
    $stmt->execute();
    $stmt->close();
}

// One POST of a row's file with this token.
function _SWUForgePost(array $row, string $token): array {
    $body = ['pgn' => (string)$row['pgn'], 'seat' => (int)$row['seat']];
    foreach (['lobbyId', 'deckName', 'opponentDeckName', 'deckId'] as $k) if ((string)$row[$k] !== '') $body[$k] = (string)$row[$k];
    return SWUForgeHttp('POST', SWUForgeConfig()['baseUrl'] . '/api/petranaki/pgn', json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ['Content-Type: application/json', 'Authorization: Bearer ' . $token], 30);
}

// Deliver one claimed row and record the outcome.
function SWUForgeDeliverRow(mysqli $conn, array $row, int $now): string {
    $id = (int)$row['id']; $usersId = (int)$row['usersId'];
    $done = function (string $status, int $http, string $error, array $extra = []) use ($conn, $id, $now) {
        _SWUForgeSetRow($conn, $id, ['status' => $status, 'lastHttp' => $http, 'lastError' => mb_substr($error, 0, 255), 'updatedAt' => $now] + $extra);
        return $status;
    };
    $retry = function (int $http, string $error) use ($conn, $id, $now, $row, $done) {
        $attempts = (int)$row['attempts'] + 1;
        if ($attempts >= SWUFORGE_MAX_ATTEMPTS) return $done('failed', $http, $error, ['attempts' => $attempts]);
        $wait = min(SWUFORGE_BACKOFF_CAP, 30 * (2 ** ($attempts - 1)));
        return $done('pending', $http, $error, ['attempts' => $attempts, 'nextAttemptAt' => $now + $wait]);
    };
    $tok = SWUForgeAccessToken($usersId, false, $now);
    if ($tok['status'] === 'unlinked' || $tok['status'] === 'relink') return $done('unlinked', 0, 'the SWU Forge link is gone');
    if ($tok['status'] !== 'ok') return $retry(0, 'token refresh unavailable');
    $r = _SWUForgePost($row, (string)$tok['token']);
    if ($r['status'] === 401) {   // an expired or revoked token: refresh once and resend
        $tok = SWUForgeAccessToken($usersId, true, $now);
        if ($tok['status'] === 'unlinked' || $tok['status'] === 'relink') return $done('unlinked', 401, 'the SWU Forge link is gone');
        if ($tok['status'] !== 'ok') return $retry(401, 'token refresh unavailable');
        $r = _SWUForgePost($row, (string)$tok['token']);
        if ($r['status'] === 401) {
            SWUForgeClearLink($conn, $usersId);   // Forge Q&A §15: a 401 after a refresh is a player who unlinked there
            return $done('unlinked', 401, strval($r['body']['error'] ?? 'unauthorized'));
        }
    }
    $http = (int)$r['status']; $b = is_array($r['body'] ?? null) ? $r['body'] : [];
    $error = strval($b['error'] ?? ($b['reason'] ?? ($r['error'] ?? '')));
    if ($http === 200) {
        SWUForgeSetProblem($conn, $usersId, '');
        return $done(!empty($b['skipped']) ? 'skipped' : 'done', 200, !empty($b['skipped']) ? strval($b['reason'] ?? 'skipped') : '', ['pgn' => '']);
    }
    if ($http === 403 || $http === 409) {   // the player has to act: tell them, and hold the game until they have
        SWUForgeSetProblem($conn, $usersId, $http === 403 ? 'not-enabled' : 'no-profile');
        if ($now - (int)$row['createdAt'] >= SWUFORGE_HELD_FOR) return $done('failed', $http, $error);
        return $done('pending', $http, $error, ['nextAttemptAt' => $now + SWUFORGE_HELD_WAIT]);
    }
    if ($http === 0 || $http === 502 || $http === 503 || $http >= 500) return $retry($http, $error !== '' ? $error : 'no answer');
    return $done('failed', $http, $error);   // 400 / 413 / 415 and anything unexpected: resending cannot help
}

// Deliver every due row (the worker's one call). Each row is claimed first, so two workers never send one row at once.
// Returns how many rows were attempted.
function SWUForgeDeliverDue(int $now = 0, int $limit = 25): int {
    $now = $now ?: time();
    if (!SWUForgeConfigured()) return 0;   // no client secret: SWU Forge is off — queued rows wait, nothing is sent
    $conn = GetLocalMySQLConnection();
    try {
        if (!SWUForgeLinkReady($conn) || !SWUForgeDeliveryReady($conn)) return 0;
        $stmt = $conn->prepare("SELECT * FROM swuforge_deliveries WHERE status = 'pending' AND nextAttemptAt <= ? ORDER BY nextAttemptAt, id LIMIT ?");
        $stmt->bind_param('ii', $now, $limit);
        $stmt->execute();
        $due = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $n = 0;
        foreach ($due as $row) {
            $claimUntil = $now + 120;
            $c = $conn->prepare("UPDATE swuforge_deliveries SET nextAttemptAt = ? WHERE id = ? AND status = 'pending' AND nextAttemptAt = ?");
            $old = (int)$row['nextAttemptAt'];
            $c->bind_param('iii', $claimUntil, $row['id'], $old);
            $c->execute();
            $claimed = $c->affected_rows === 1;
            $c->close();
            if (!$claimed) continue;
            try {
                SWUForgeDeliverRow($conn, $row, $now);
            } catch (Throwable $e) {
                error_log('SWU Forge: delivering row ' . (int)$row['id'] . ' failed: ' . $e->getMessage());
            }
            $n++;
        }
        return $n;
    } finally {
        $conn->close();
    }
}
