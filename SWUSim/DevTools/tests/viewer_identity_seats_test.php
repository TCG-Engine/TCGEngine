<?php
// Viewer identity vs SEAT COUNT.
//
// NormalizeViewerIdentity's $maxSeats parameter DEFAULTS TO 2, and every caller that forgets it
// silently mis-identifies the far seats of a 3-4 player game:
//   • '3' hits the pre-'S' legacy rule ("playerID 3 meant spectator"), which is correct ONLY below
//     three seats — so P3 becomes a spectator;
//   • '4' fails the `$seat <= $maxSeats` range check and falls through to the invalid identity.
// Both then fail their auth check. That is what produced "Invalid auth key." for a Team Suns player
// submitting a bug report, and the same omission blocked replay downloads (2026-08-26).
//
// These assertions pin BOTH directions: the far seats must be real at four, and the legacy spectator
// rule must survive at two.
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
$root = __DIR__ . '/../../..';
require_once $root . '/Core/ViewerIdentity.php';

function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } echo "  ok: $msg\n"; }

// ── The root -> seat-count mapping every caller must pass ──
check(SimGameMaxSeats('SWUSim') === 4, 'SWUSim is a 4-seat root');
check(SimGameMaxSeats('GrandArchiveSim') === 2, 'GrandArchiveSim is a 2-seat root');
check(SimGameMaxSeats('') === 2, 'an unknown root is treated as 2 seats');

// ── FOUR seats: 1-4 are all real, actable seats ──
foreach ([1, 2, 3, 4] as $seat) {
    $v = NormalizeViewerIdentity(strval($seat), 4);
    check($v['viewerID']    === strval($seat), "4-seat: P{$seat} keeps its viewerID");
    check($v['viewerSeat']  === $seat,         "4-seat: P{$seat} resolves to seat {$seat}");
    check($v['isSpectator'] === false,         "4-seat: P{$seat} is NOT a spectator");
    check($v['canAct']      === true,          "4-seat: P{$seat} can act");
}

// ⚠ THE REGRESSION: with the seat count omitted, the far seats are lost.
$p3default = NormalizeViewerIdentity('3');
$p4default = NormalizeViewerIdentity('4');
check($p3default['isSpectator'] === true,  'default maxSeats: P3 degrades to SPECTATOR (the bug)');
check($p4default['viewerID']    === '',    'default maxSeats: P4 degrades to an INVALID identity (the bug)');
check(NormalizeViewerIdentity('3', 4)['isSpectator'] === false,
      'passing the seat count is what rescues P3 — this is the whole fix');

// ── TWO seats: the legacy "3 means spectator" rule must still hold ──
$p3two = NormalizeViewerIdentity('3', 2);
check($p3two['isSpectator'] === true, '2-seat: P3 is still the legacy spectator alias');
check(NormalizeViewerIdentity('4', 2)['viewerID'] === '', '2-seat: P4 is not a valid identity');
foreach ([1, 2] as $seat) {
    check(NormalizeViewerIdentity(strval($seat), 2)['canAct'] === true, "2-seat: P{$seat} can act");
}

// ── Spectators and junk, at both seat counts ──
foreach ([2, 4] as $ms) {
    check(NormalizeViewerIdentity('S', $ms)['isSpectator'] === true, "{$ms}-seat: 'S' is a spectator");
    check(NormalizeViewerIdentity('', $ms)['isSpectator']  === true, "{$ms}-seat: empty is a spectator");
    check(NormalizeViewerIdentity('0', $ms)['viewerID'] === '',   "{$ms}-seat: seat 0 is invalid");
    check(NormalizeViewerIdentity('99', $ms)['viewerID'] === '',  "{$ms}-seat: seat 99 is invalid");
    check(NormalizeViewerIdentity('abc', $ms)['viewerID'] === '', "{$ms}-seat: junk is invalid");
}

// A spectator has no seat, so the cookie auth fallback (now gated on viewerSeat) must not apply to them.
check(NormalizeViewerIdentity('S', 4)['viewerSeat'] === null, 'a spectator has no seat to fall back for');
check(NormalizeViewerIdentity('4', 4)['viewerSeat'] === 4,    'P4 has a seat, so it may use the fallback');

echo "PASS\n";
