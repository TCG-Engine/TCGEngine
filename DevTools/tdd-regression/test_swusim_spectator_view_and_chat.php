<?php
// ⚠ CLI ONLY — run it as a PROCESS, never through the browser:
//     docker exec -w /var/www/html/TCGEngine <container> php -d xdebug.mode=off \
//       DevTools/tdd-regression/test_swusim_spectator_view_and_chat.php
// It fetches pages from the very server that would be serving it, so in the WEB SAPI it blocks on its
// own worker and every fetch comes back EMPTY — at which point two "a spectator gets NO …" assertions
// pass TRIVIALLY against a blank page. The length check below is what caught that; keep it.
//
// SPECTATORS, two owner rulings (2026-09-26):
//   1. A Twin Suns spectator must be able to watch from EVERY live seat. The picker was two hardcoded
//      buttons, so P3 and P4 were unreachable from the UI even though the backend already accepted any
//      perspective >= 1 (Core/ViewerIdentity.php). The clamp was purely in NextTurn.php's markup.
//   2. A spectator may NOT send chat, and is told NOTHING about it — no input, no send button, and
//      deliberately no notice. Contrast a guest, who DOES get "Log in to chat." because logging in is
//      an action they can take; there is nothing a spectator could do, so a notice would be noise.
//
// Asserted over real HTTP against a real four-seat game, because both rulings are about what the PAGE
// contains — a unit test on the helpers would pass while the markup still shipped two buttons.
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
if (PHP_SAPI !== 'cli') { header('Content-Type: text/plain'); echo "Run this from the CLI — see the header.\n"; exit(1); }

$FAILS = 0;
function check($cond, $msg, $extra = null) {
    global $FAILS;
    echo ($cond ? '  ok: ' : '  BAD: ') . $msg . (($cond || $extra === null) ? '' : '  ' . json_encode($extra)) . "\n";
    if (!$cond) $FAILS++;
}

$repo = dirname(__DIR__, 2);
$game = '9932' . substr((string)time(), -4);        // fresh id per run — the web process caches a game as first created
$mk   = escapeshellarg($repo . '/SWUSim/DevTools/make-fourseat-fixture.php');
exec('php -d xdebug.mode=off ' . $mk . ' --game=' . escapeshellarg($game) . ' 2>&1', $mkOut, $mkRc);
check($mkRc === 0, 'four-seat fixture built', implode(' | ', array_slice($mkOut, 0, 2)));

// The container serves on :80; :3400 is the HOST's port mapping and does not resolve in here.
$base = 'http://localhost/TCGEngine';
$get  = function (string $url): string {
    $ctx = stream_context_create(['http' => ['timeout' => 25, 'ignore_errors' => true]]);
    return (string)@file_get_contents($url, false, $ctx);
};
$board = $base . '/NextTurn.php?folderPath=SWUSim&gameName=' . rawurlencode($game);

// ── 1. THE SEAT PICKER ───────────────────────────────────────────────────────────────────────────
$spec = $get($board . '&playerID=S');
check(strlen($spec) > 10000, 'the spectator board renders', strlen($spec));
preg_match_all('/onclick=.SetSpectatorPerspective\((\d)\)./', $spec, $m);
$seats = array_map('intval', $m[1] ?? []);
sort($seats);
check($seats === [1, 2, 3, 4],
      'a four-seat game offers a perspective button for EVERY seat (was hardcoded to 1 and 2)', $seats);

// ── 2. THE CHAT COMPOSER ─────────────────────────────────────────────────────────────────────────
// ⚠ THIS HALF MUST RUN LOGGED IN, and that is the whole difference between a real guard and a vacuous
// one. A session-less spectator falls into the GUEST branch, which never renders a composer anyway —
// so asserting "no chat input" anonymously can NEVER fail, however broken the spectator gate is. The
// logged-in spectator is the only viewer who would otherwise get the composer. (Same lesson as the
// whisper suites that went red in cda64549: a fixture that cannot log in is not testing what it says.)
require_once __DIR__ . '/fixtures/swusim_chat_http_helpers.php';
$specIn = swuchat_http('NextTurn.php?folderPath=SWUSim&gameName=' . rawurlencode($game) . '&playerID=S');
check(strlen($specIn) > 10000, 'the LOGGED-IN spectator board renders', strlen($specIn));
check(strpos($specIn, "id='chatGuestNote'") === false,
      'the session really is logged in (no guest note) — else the checks below are vacuous');
check(strpos($specIn, "id='chatText'") === false,    'a LOGGED-IN spectator gets NO chat input');
check(strpos($specIn, "id='chatSendBtn'") === false, 'a LOGGED-IN spectator gets NO send button');

check(strpos($spec, "id='chatText'") === false,    'an anonymous spectator gets no chat input either');
check(strpos($spec, "id='chatGuestNote'") === false,
      'a spectator gets NO explanation either — the ruling is that they are told nothing');

// THE CONTROL. Without it, "render nothing for everyone" would pass every assertion above. A seated
// viewer with no session is a GUEST, and a guest still gets the login note — that branch is untouched.
$seated = $get($board . '&playerID=3');
check(strpos($seated, "id='spectatorControls'") === false, 'a seated viewer gets no spectator picker');
check(strpos($seated, "id='chatGuestNote'") !== false,
      'a seated GUEST still gets the login note — the spectator branch did not swallow it');

// ── 3. THE SERVER GATE ───────────────────────────────────────────────────────────────────────────
// Hiding a control is not disabling a feature; a hand-crafted POST must be refused too.
$send = $base . '/SubmitChat.php?folderPath=SWUSim&gameName=' . rawurlencode($game) . '&chatText=hi&playerID=';
check(trim($get($send . 'S')) === 'Spectators cannot chat.', 'SubmitChat refuses a spectator');
check(trim($get($send . '3')) === 'Log in to chat.',
      'SubmitChat still refuses a seated guest for the OTHER reason — the two gates stay distinct');

exec('php -d xdebug.mode=off ' . $mk . ' --game=' . escapeshellarg($game) . ' --remove 2>&1');

echo $FAILS === 0 ? "\nALL PASS\n" : "\n{$FAILS} FAILED\n";
