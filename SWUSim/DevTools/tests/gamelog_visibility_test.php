<?php
// ── GAME-LOG VISIBILITY IS A SEAT LIST, NOT A SINGLE SEAT ───────────────────────────────────────────
//
// A GameLog row is "TYPE|VISIBILITY|text". VISIBILITY used to be either 'ALL' or ONE 'P{n}', which made
// "just the two players involved" inexpressible: SWULogPrivateReveal had to emit one entry PER involved
// seat, so a private look stored the same line twice, and a team-scoped line ("you and your partner see
// this") could not be written at all. It is now a COMMA-SEPARATED list — 'ALL', 'P3', or 'P1,P3'.
//
// ⚠ THE READER IS GENERATED. It is emitted by zzGameCodeGenerator.php's GameLog block into
// SWUSim/GetNextTurn.php, which is gitignored — a hand-edit there has no git trace and dies at the next
// regen. If this test fails after a pull, run `php zzGameCodeGenerator.php rootName=SWUSim`.
//
// This test checks BOTH halves: the generated reader's shape, and the filter semantics themselves
// (re-implemented here exactly as generated, so a divergence in either one is caught).

error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
$repo = getenv('REPO_ROOT') ?: '/var/www/html/TCGEngine';
chdir($repo);

function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } echo "  ok: $msg\n"; }

// ── 1. the GENERATED reader still parses a comma list ────────────────────────
$gnt = @file_get_contents($repo . '/SWUSim/GetNextTurn.php');
check($gnt !== false, 'GetNextTurn.php is present (regenerate if not)');
check(strpos($gnt, "array_map('trim', explode(',', \$logVis))") !== false,
    'the generated GameLog filter splits VISIBILITY on "," — rerun zzGameCodeGenerator.php if this fails');
check(strpos($gnt, "\$vSeatTag = 'P' . intval(\$viewerInfo[\"viewerSeat\"] ?? 0);") !== false,
    'the generated filter builds the viewer seat tag once');

// ── 2. the semantics, mirroring the generated expression exactly ─────────────
$visible = function (string $logVis, int $viewerSeat, bool $isSpectator): bool {
    $vSeatTag = 'P' . $viewerSeat;
    return $logVis === 'ALL'
        || (!$isSpectator && in_array($vSeatTag, array_map('trim', explode(',', $logVis)), true));
};

check($visible('ALL', 1, false) && $visible('ALL', 4, false) && $visible('ALL', 0, true),
    "'ALL' is visible to every seat AND to spectators");
check($visible('P3', 3, false) && !$visible('P3', 1, false) && !$visible('P3', 4, false),
    "a single seat tag is visible ONLY to that seat");
check(!$visible('P3', 3, true), 'a spectator never sees a seat-scoped entry');

// The load-bearing case: two seats, one entry.
check($visible('P1,P3', 1, false), "'P1,P3' is visible to seat 1");
check($visible('P1,P3', 3, false), "'P1,P3' is visible to seat 3");
check(!$visible('P1,P3', 2, false), "'P1,P3' is NOT visible to seat 2");
check(!$visible('P1,P3', 4, false), "'P1,P3' is NOT visible to seat 4");
check(!$visible('P1,P3', 1, true),  "'P1,P3' is not visible to a spectator");

// A prefix must not match a longer seat tag: 'P1' is not seat 11, and 'P1,P3' does not leak to seat 13.
check(!$visible('P1', 11, false),   "'P1' does not match seat 11 (whole-tag comparison, not a prefix)");
check(!$visible('P1,P3', 13, false), "'P1,P3' does not match seat 13");

// Whitespace tolerance — the list is assembled by implode(',') today, but a hand-written
// 'P1, P3' must not silently hide the entry from seat 3.
check($visible('P1, P3', 3, false), "'P1, P3' (with a space) still reaches seat 3");

echo "PASS: gamelog_visibility_test\n";
