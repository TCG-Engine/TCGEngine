<?php
// RUN VIA CLI:
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swusim_inactivity_no_legacy_timer.php
//
// Inactivity clock stage 3 — pins the DELETION of the legacy idle timer (2026-09-17), so a second
// inactivity system cannot creep back in beside the real one.
// The deleted code was inert three ways (no #iconHolder, no handler for modes 100005/100006, no caller
// for MatchInactivityForfeit) and reset on document.onmousemove — moving the pointer counted as playing.
// Spec: docs/superpowers/specs/2026-09-17-swusim-inactivity-timer-and-kick-design.md
// Source scan only: it proves the old code is GONE and the new seams are WIRED. Behaviour is covered by
// test_swusim_inactivity_clock_signal.php, test_swusim_kick_vote_flow.php and the browser probe.
header('Content-Type: text/plain');
$ROOT = __DIR__ . '/../..';
$read = function (string $rel) use ($ROOT): string {
    $p = "$ROOT/$rel";
    return is_file($p) ? strval(file_get_contents($p)) : '';
};
// Scan CODE, not prose: the deletion is documented in a comment that names the very things it removed,
// and a raw substring scan would match its own tombstone. Strips /* … */ and // … comments.
$code = function (string $text): string {
    $text = preg_replace('#/\*.*?\*/#s', ' ', $text);
    return preg_replace('#(^|\s)//[^\n]*#', ' ', strval($text));
};

$checks = [];
$nextTurn = $code($read('NextTurn.php'));
$checks['NextTurn.php readable'] = $nextTurn !== '';

// ── the legacy timer is gone ──
foreach ([
    'CheckIdleTime('        => 'the 1s idle poll',
    'IDLE_TIMEOUT'          => 'the 30s constant',
    '_idleSecondsCounter'   => 'the idle counter',
    'inactivityWarningPopup'=> 'the warning popup',
    'inactivePopup'         => 'the "you are inactive" popup',
    'document.onmousemove'  => 'the mousemove reset (a pointer wiggle is NOT playing)',
] as $needle => $what) {
    $checks["gone: $what"] = strpos($nextTurn, $needle) === false;
}
// The two handler-less modes must not be posted from anywhere.
$checks['gone: mode 100005 post'] = strpos($nextTurn, '"100005"') === false;
$checks['gone: mode 100006 post'] = strpos($nextTurn, '"100006"') === false;
// ⚠ _lastUpdate lived INSIDE that block and is used by the live poll — it must survive.
$checks['kept: _lastUpdate']      = strpos($nextTurn, 'var _lastUpdate = 0') !== false;

// ── the dead forfeit function is gone, everywhere ──
$checks['gone: MatchInactivityForfeit()']    = strpos($read('Core/Match/MatchFlow.php'), 'function MatchInactivityForfeit') === false;
$checks['gone: SWUMatchInactivityForfeit()'] = strpos($read('SWUSim/MatchFlow.php'), 'function SWUMatchInactivityForfeit') === false;

// ── the real clock is wired ──
$checks['wired: presence store']       = strpos($read('Core/GamePresence.php'), 'function PresenceStampAction') !== false;
$checks['wired: clock policy']         = strpos($read('SWUSim/Custom/InactivityClock.php'), 'function SWUProgressFingerprint') !== false;
$checks['wired: removal']              = strpos($read('SWUSim/Custom/InactivityClock.php'), 'function SWUApplyKick') !== false;
$checks['wired: schema include']       = strpos($read('Schemas/SWUSim/GameSchema.txt'), 'ServerInclude: /Custom/InactivityClock.php') !== false;
$checks['wired: fingerprint snapshot'] = strpos($read('ProcessInput.php'), 'gEngineProgressFingerprintBefore') !== false;
$checks['wired: stamp in the engine']  = strpos($read('Core/EngineActionRunner.php'), 'PresenceStampAction') !== false;
$checks['wired: vote modes']           = strpos($read('Core/EngineActionRunner.php'), 'case 10021') !== false
                                         && strpos($read('Core/EngineActionRunner.php'), 'case 10022') !== false;
$checks['wired: poll heartbeat']       = strpos($read('zzGameCodeGenerator.php'), 'PresenceTouchSeat') !== false;
$checks['wired: client sink']          = strpos($read('Core/jsInclude.js'), 'TCGPresenceSink') !== false;
$checks['wired: overlay']              = strpos($read('SWUSim/Custom/GameLayoutShared.php'), 'swuKickVote') !== false;
// The dev-only seams must stay gated.
foreach (['SWUSim/DevTools/zz_presence_dump.php', 'SWUSim/DevTools/zz_presence_poke.php'] as $f) {
    $checks["gated: " . basename($f)] = strpos($read($f), 'SimGameIsDevelopmentEnvironment()') !== false;
}

$fail = 0;
foreach ($checks as $name => $ok) { echo ($ok ? 'PASS' : 'FAIL') . "  $name\n"; if (!$ok) $fail++; }
echo $fail === 0 ? "\nPASS (" . count($checks) . ")\n" : "\nFAIL ($fail of " . count($checks) . ")\n";
exit($fail === 0 ? 0 : 1);
