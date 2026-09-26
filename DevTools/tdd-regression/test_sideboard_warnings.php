<?php
// Sideboard countdown warnings — owner request 2026-09-26: "now that we have gamelogs in the
// sideboard experience, let's add a 90s warning as well as a 30s warning".
//
// The sideboard has a 180s deadline (MATCH_SIDEBOARD_SECONDS) after which any un-ready seat is
// auto-submitted with its PRIOR deck. Until now nothing told the player that was coming: the
// deadline was computed server-side and never surfaced anywhere. These warnings land in the match
// conversation, which the Sideboard page's chat panel already polls every 2s.
//
// ⚠ THEY ARE EMITTED FROM A READ (GetChat.php's match branch), because that poll is the only thing
// that reliably ticks while NEITHER player has submitted — SubmitSideboard.php's poll only starts
// after you submit, and a spectator's GetNextTurn poll may not exist. That is the same shape
// GetNextTurn.php already uses for MatchSideboardTimeoutCheck. Being called from a read is exactly
// why every assertion below hammers the function repeatedly: it MUST be idempotent.
//
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_sideboard_warnings.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
chdir('/var/www/html/TCGEngine');
include_once __DIR__ . '/../../Core/Match/MatchFlow.php';
// ⚠ Core/NetworkingLibraries.php is DELIBERATELY NOT INCLUDED HERE. MatchFlow does not pull it in
// (measured), so the emitter has to load its own chat dependency — and this file reads the rows back
// with GetChatMessagesSince, which lives in that same library. If the emitter ever stops loading it,
// the first $warnRows() call dies with an undefined function instead of quietly posting nothing.
// That makes the lazy include load-bearing for this whole file rather than a line nobody exercises.

$ROOT = 'SBWarnTest';
MatchRegisterHooks($ROOT, [
    'resolveLobbyDecks' => function ($l) { return null; },
    'validateDeck'      => function ($d, $f) { return true; },
    'setupGame'         => function ($lobby, $opts) {
        $n = 'sbw' . substr(md5(uniqid('', true)), 0, 8);
        @mkdir(__DIR__ . '/../../SBWarnTest/Games/' . $n, 0777, true);
        return $n;
    },
]);

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };
$players = [1 => ['originalDeck' => ['mainDeck' => ['cardX']], 'authKey' => 'a1'],
            2 => ['originalDeck' => ['mainDeck' => ['cardY']], 'authKey' => 'a2']];

// Every warning row in a match's conversation, newest last.
$warnRows = function ($matchId) {
    $rows = GetChatMessagesSince('m:' . $matchId, 0, ['viewerID' => '1', 'seat' => 1]);
    $out = [];
    foreach ((array)$rows as $r) {
        $t = strval($r['text'] ?? '');
        if (strpos($t, 'seconds left') !== false) $out[] = $t;
    }
    return $out;
};
// Wind the clock forward by moving the DEADLINE back — the same trick the private-timer guard uses.
$setRemaining = function ($matchId, $seconds) use ($ROOT) {
    MatchWithLock($ROOT, $matchId, function (&$mm) use ($seconds) { $mm['sideboardDeadline'] = time() + $seconds; });
};

// ── A. each mark fires ONCE, in order, and repeated ticks never re-post ─────────────────────────
$a = MatchCreate($ROOT, 'premier', 'bo3', $players, false);
MatchBeginSideboarding($ROOT, $a, 1);

$setRemaining($a, 120);
for ($i = 0; $i < 3; $i++) MatchSideboardWarningCheck($ROOT, $a);
$check(count($warnRows($a)) === 0, 'A: nothing at 120s remaining (before the first mark)');

$setRemaining($a, 89);
for ($i = 0; $i < 5; $i++) MatchSideboardWarningCheck($ROOT, $a);   // five polls, one warning
$rows = $warnRows($a);
$check(count($rows) === 1, 'A: exactly ONE row after crossing 90s, across five ticks (got ' . count($rows) . ')');
$check(count($rows) === 1 && strpos($rows[0], '90 seconds') !== false, 'A: it is the 90s warning');

$setRemaining($a, 60);
for ($i = 0; $i < 3; $i++) MatchSideboardWarningCheck($ROOT, $a);
$check(count($warnRows($a)) === 1, 'A: still one row between the marks');

$setRemaining($a, 29);
for ($i = 0; $i < 5; $i++) MatchSideboardWarningCheck($ROOT, $a);
$rows = $warnRows($a);
$check(count($rows) === 2, 'A: exactly TWO rows after crossing 30s (got ' . count($rows) . ')');
$check(count($rows) === 2 && strpos($rows[1], '30 seconds') !== false, 'A: the second is the 30s warning');
$check(count($rows) === 2 && strpos($rows[1], 'automatically') !== false,
    'A: the 30s warning says the deck will be submitted automatically');

// ── B. a clock that JUMPS past both marks still emits both ──────────────────────────────────────
// A tab that was hidden (or a server that was busy) can go from 120s to 10s between two polls. The
// player must still be told what happened rather than silently losing both warnings.
$b = MatchCreate($ROOT, 'premier', 'bo3', $players, false);
MatchBeginSideboarding($ROOT, $b, 1);
$setRemaining($b, 10);
MatchSideboardWarningCheck($ROOT, $b);
$rows = $warnRows($b);
$check(count($rows) === 2, 'B: a jump straight to 10s remaining emits BOTH marks (got ' . count($rows) . ')');
$check(count($rows) === 2 && strpos($rows[0], '90 seconds') !== false && strpos($rows[1], '30 seconds') !== false,
    'B: and in order, 90 then 30');

// ── C. PRIVATE matches have no deadline, so they get no warnings ────────────────────────────────
$c = MatchCreate($ROOT, 'premier', 'bo3', $players, true);
MatchBeginSideboarding($ROOT, $c, 1);
$m = MatchRead($ROOT, $c);
// ⚠ C IS COVERED TWICE OVER AND SO CANNOT ISOLATE EITHER GUARD. Measured: deleting the
// `$deadline <= 0` check leaves this green (a missing deadline reads as 0, and `$remaining < 0`
// catches it), and deleting `$remaining < 0` also leaves it green. Both were kept deliberately —
// see the note on the emitter. Recorded here so nobody later reads C as proof of the first guard.
$check(!isset($m['sideboardDeadline']), 'C: private match still has no deadline');
for ($i = 0; $i < 3; $i++) MatchSideboardWarningCheck($ROOT, $c);
$check(count($warnRows($c)) === 0, 'C: PRIVATE match is never warned');

// ── D. nothing once the deadline has passed, and nothing outside sideboarding ───────────────────
// Past the deadline the auto-submit has taken over; a warning then is noise about a decision the
// player no longer has. And a match that is not sideboarding must never be touched at all.
$d = MatchCreate($ROOT, 'premier', 'bo3', $players, false);
MatchBeginSideboarding($ROOT, $d, 1);
$setRemaining($d, -5);
MatchSideboardWarningCheck($ROOT, $d);
$check(count($warnRows($d)) === 0, 'D: no warning once the deadline has already passed');

$e = MatchCreate($ROOT, 'premier', 'bo3', $players, false);   // never entered sideboarding
MatchSideboardWarningCheck($ROOT, $e);
$check(count($warnRows($e)) === 0, 'D: a match that is not sideboarding is never warned');

// ── E. the marks reset when the next game spawns, so game 3 warns too ───────────────────────────
// sideboardWarned lives beside sideboard/sideboardDeadline and must be cleared with them, or a Bo3's
// SECOND sideboard is silent.
$f = MatchCreate($ROOT, 'premier', 'bo3', $players, false);
MatchBeginSideboarding($ROOT, $f, 1);
$setRemaining($f, 29);
MatchSideboardWarningCheck($ROOT, $f);
$check(count($warnRows($f)) === 2, 'E: first sideboard warned twice');
MatchSubmitSideboardDeck($ROOT, $f, 1, ['mainDeck' => ['cardX']]);
MatchSubmitSideboardDeck($ROOT, $f, 2, ['mainDeck' => ['cardY']]);
// ⚠ SUBMITTING DOES NOT SPAWN. SubmitSideboard.php calls this explicitly once both are in, and the
// clear lives in the spawn — without this line the assertion below tests nothing but my own fixture.
MatchMaybeSpawnAfterSideboard($ROOT, $f);
$m = MatchRead($ROOT, $f);
$check(($m['state'] ?? '') === 'in_progress', 'E: the next game actually spawned (guards the assertion below)');
$check(!isset($m['sideboardWarned']), 'E: the sent-marks list is cleared when the next game spawns');

// ── F. the emitter is actually WIRED to the poll that runs ─────────────────────────────────────
// Everything above drives MatchSideboardWarningCheck() directly. That proves the emitter and proves
// nothing about whether anything CALLS it — and an emitter nobody calls is dead code that ships
// looking complete. GetChat.php's match branch is the tick (see the note at the top); assert the
// call site exists, sits on the SWUSim match path, and is below the auth gate.
$getChat = (string)file_get_contents(__DIR__ . '/../../GetChat.php');
$check(strpos($getChat, 'SWUSideboardWarningCheck(') !== false,
    'F: GetChat.php calls the warning check');
$posAuth = strpos($getChat, 'ChatScopeAuthOk');
$posCall = strpos($getChat, 'SWUSideboardWarningCheck(');
$check($posAuth !== false && $posCall !== false && $posCall > $posAuth,
    'F: it is called AFTER the auth gate, never for a caller who cannot prove their seat');
$check(strpos($getChat, "\$scope['kind'] === 'match'") !== false,
    'F: it is gated to the MATCH scope');
$check(function_exists('MatchSideboardWarningCheck'),
    'F: the Core emitter the SWUSim wrapper forwards to exists');

// ── G. the chat writer is secured BEFORE any mark is claimed ───────────────────────────────────
// A real bug caught during this change: ChatAppendMessage lives in Core/NetworkingLibraries.php,
// which SWUSim/MatchFlow.php does NOT pull in on its own — only GetChat.php happened to have it
// loaded. With the availability check placed AFTER the lock, any other entry point would claim the
// mark, fail to post, and lose the warning permanently (the mark reads as sent forever after).
// This is an ordering invariant inside one function, which no behavioural fixture can observe:
// the marks are consumed either way, and the difference only shows on a caller that lacks the lib.
$flow    = (string)file_get_contents(__DIR__ . '/../../Core/Match/MatchFlow.php');
$emitter = strstr($flow, 'function MatchSideboardWarningCheck');
$emitter = $emitter === false ? '' : substr($emitter, 0, 2600);
$posGuard = strpos($emitter, "function_exists('ChatAppendMessage')");
$posLock  = strpos($emitter, 'MatchWithLock');
$check($posGuard !== false && $posLock !== false && $posGuard < $posLock,
    'G: the ChatAppendMessage guard runs BEFORE the marks are claimed under the lock');
// ⚠ AND THE LIBRARY IS REQUIRED AT FILE SCOPE, NOT INSIDE A FUNCTION. An include inside a function
// declares that file's FUNCTIONS globally but makes its top-level VARIABLES function-local, so
// NetworkingLibraries' `$APCuEnabled` never reaches the global scope and GetChatMessagesSince —
// which reads `global $APCuEnabled` — silently returns [] for every caller. That is precisely what
// the first cut did, and this file's own reads are what exposed it.
$beforeFirstFunction = substr($flow, 0, strpos($flow, "\nfunction ") ?: strlen($flow));
$check(strpos($beforeFirstFunction, 'NetworkingLibraries.php') !== false,
    'G: NetworkingLibraries is required at FILE scope, so its globals are not eaten');
$check(strpos($emitter, 'NetworkingLibraries.php') === false,
    'G: and the emitter does not re-include it from inside the function');

// cleanup — Games AND Matches (see the note in test_sideboard_private_no_timer.php)
foreach ([$a, $b, $c, $d, $e, $f] as $id) { $p = MatchPath($ROOT, $id); if (is_file($p)) @unlink($p); }
$sbRoot = __DIR__ . '/../../SBWarnTest';
foreach (['Games', 'Matches'] as $sub) {
    @array_map('unlink', glob("$sbRoot/$sub/*/*") ?: []);
    @array_map('rmdir',  glob("$sbRoot/$sub/*") ?: []);
    @array_map('unlink', glob("$sbRoot/$sub/*") ?: []);
    @rmdir("$sbRoot/$sub");
}
@rmdir($sbRoot);
echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
