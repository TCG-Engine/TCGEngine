<?php
// Maintenance mode — a write freeze, not a site shutdown.
//
// Toggled from zzMaintenanceMode.php?rootName=<app>. Read by the write endpoints.
//
// ⚠ THE FLAG IS A FILE, NEVER A DATABASE ROW. During the SET_NNN migration the database is the
// thing being rebuilt and swapped out from under everything — a gate that had to query it would
// fail at exactly the moment it is needed. It also means this keeps working if MySQL is down.
//
// Two levels, because the migration has two phases with different needs:
//
//   stats  Blocks SubmitGameResult / SubmitManualGameResult. The site stays fully up and readable:
//          meta pages, deck pages and card stats all work. This is what the DATABASE migration
//          needs — the aggregating rebuild reads a snapshot, so any stat write between the build
//          and the RENAME lands in the table that is about to become _old and is silently lost.
//
//   full   Also blocks deck saves (favoritedeck, deck gamestate files). This is what the DECK-FILE
//          rewrite needs — autosave-on-open racing a format change is what caused the Leader2
//          sideboard data loss.
//
// Reads are never blocked at either level. Nothing about the migration requires it (the rebuild
// takes no shared locks under ROW binlog + REPEATABLE-READ), and a site that reads fine with stats
// paused is a much better outage than one that looks dead.
//
// This deliberately does NOT write an .htaccess. That approach depends on AllowOverride being on,
// can 500 the whole site if a directive is unsupported (mod_headers is not always loaded), and can
// lock the operator out of the very tools needed to fix it.
//
// Design: docs/superpowers/specs/2026-08-03-swudeck-setnnn-identity-migration-design.md §9

if (!defined('SWU_MAINTENANCE_LEVELS')) define('SWU_MAINTENANCE_LEVELS', 'off,stats,full');

function SWUMaintenanceRoot(): string
{
    return dirname(__DIR__, 2);
}

// One flag file per app root, so the two boxes are independent.
function SWUMaintenanceFlagPath(string $rootName): string
{
    $rootName = preg_replace('/[^A-Za-z0-9_]/', '', $rootName);
    return SWUMaintenanceRoot() . '/' . $rootName . '/maintenance.json';
}

// Current state. Absent file == off; that is the normal case and costs one stat().
//
// A file that EXISTS but will not parse is treated as 'full', not as off: somebody deliberately put
// it there, and the failure mode of guessing "off" is losing writes during a migration, which is
// unrecoverable. Guessing "full" merely pauses writes, which is loud and immediately noticed.
function SWUMaintenanceState(string $rootName): array
{
    $off = ['level' => 'off', 'reason' => '', 'since' => null, 'by' => '', 'malformed' => false];
    $path = SWUMaintenanceFlagPath($rootName);
    if (!is_file($path)) return $off;

    $raw = @file_get_contents($path);
    $data = $raw === false ? null : json_decode($raw, true);
    if (!is_array($data) || !isset($data['level'])) {
        error_log("SWU maintenance: $path exists but is unreadable/malformed — failing CLOSED (full).");
        return ['level' => 'full', 'reason' => 'flag file unreadable', 'since' => @filemtime($path),
                'by' => '', 'malformed' => true];
    }
    $level = (string)$data['level'];
    if (!in_array($level, ['off', 'stats', 'full'], true)) $level = 'full';

    return [
        'level'     => $level,
        'reason'    => (string)($data['reason'] ?? ''),
        'since'     => isset($data['since']) ? (int)$data['since'] : @filemtime($path),
        'by'        => (string)($data['by'] ?? ''),
        'malformed' => false,
    ];
}

// Write the flag. 'off' removes it, so the normal state is "no file" rather than a file saying off —
// one less thing that can be wrong, and it makes `ls` a valid status check.
function SWUMaintenanceSet(string $rootName, string $level, string $reason, string $by): array
{
    $path = SWUMaintenanceFlagPath($rootName);
    if (!in_array($level, ['off', 'stats', 'full'], true)) {
        return ['ok' => false, 'error' => "unknown level '$level'"];
    }
    if ($level === 'off') {
        if (is_file($path) && !@unlink($path)) {
            return ['ok' => false, 'error' => "could not remove $path — check permissions"];
        }
        return ['ok' => true, 'path' => $path];
    }

    $dir = dirname($path);
    if (!is_dir($dir)) return ['ok' => false, 'error' => "no such directory: $dir"];

    $payload = json_encode([
        'level'  => $level,
        'reason' => $reason,
        'since'  => time(),
        'by'     => $by,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    // Write-then-rename: a reader must never see a half-written file, because a half-written file
    // parses as malformed and (per the rule above) escalates everyone to 'full'.
    $tmp = $path . '.tmp';
    if (@file_put_contents($tmp, $payload) === false) {
        return ['ok' => false, 'error' => "could not write $tmp — check permissions on $dir"];
    }
    if (!@rename($tmp, $path)) {
        @unlink($tmp);
        return ['ok' => false, 'error' => "could not move $tmp into place"];
    }
    return ['ok' => true, 'path' => $path];
}

// Is a given kind of write allowed right now? $need is 'stats' or 'deck'.
function SWUMaintenanceBlocks(string $rootName, string $need): bool
{
    $level = SWUMaintenanceState($rootName)['level'];
    if ($level === 'off') return false;
    if ($level === 'full') return true;
    return $need === 'stats';          // level 'stats' blocks stats writes only
}

// Gate for an API endpoint: 503 and exit if this write is frozen.
//
// 503 + Retry-After is the correct signal for a well-behaved client — it means "try again later",
// not "your request was wrong" — and it is what Karabast needs to see in order to retry rather than
// discard the submission. The body matches the endpoint's existing {success,error} shape, so no
// consumer has to learn a new one. Success responses are untouched; this is an availability signal
// that only exists while an operator has explicitly turned maintenance on.
function SWUMaintenanceRequire(string $rootName, string $need, int $retryAfter = 1800): void
{
    if (!SWUMaintenanceBlocks($rootName, $need)) return;

    $state  = SWUMaintenanceState($rootName);
    $reason = $state['reason'] !== '' ? $state['reason'] : 'Scheduled maintenance';

    http_response_code(503);
    header('Retry-After: ' . $retryAfter);

    // A browser hitting a user-facing page should get a sentence, not a JSON blob. Sniff rather
    // than make every call site declare it: getting this wrong is cosmetic, and a wrong guess
    // still carries the 503 and the Retry-After, which are the parts that matter.
    if (_SWUMaintenanceWantsJson()) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error'   => 'Temporarily unavailable for scheduled maintenance. Please retry later.',
            'reason'  => $reason,
        ]);
    } else {
        header('Content-Type: text/html; charset=utf-8');
        $r = htmlspecialchars($reason, ENT_QUOTES, 'UTF-8');
        echo "<!doctype html><meta charset='utf-8'><title>Down for maintenance</title>"
           . "<style>body{font:15px/1.6 system-ui,sans-serif;max-width:34em;margin:15vh auto;padding:0 1.5em}"
           . "h1{font-size:20px}p{opacity:.85}</style>"
           . "<h1>Down for maintenance</h1>"
           . "<p>Saving is paused for a short while: <b>$r</b></p>"
           . "<p>Browsing still works — nothing you have saved is affected. Please try again shortly.</p>";
    }
    exit;
}

function _SWUMaintenanceWantsJson(): bool
{
    $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
    if (strpos($accept, 'application/json') !== false) return true;
    if (strpos($accept, 'text/html') !== false) return false;
    // No usable Accept header: an XHR or a server-to-server POST. Both want JSON; a browser
    // navigation always sends text/html, so this defaults the ambiguous case correctly.
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) return true;
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}
