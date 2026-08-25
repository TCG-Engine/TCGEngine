<?php
// "Look at an opponent's hand / resources" showed CARD BACKS above two seats — the pick was BLIND.
//
// This is bug report #964 reintroduced by SEAT COUNT. The transport reveal that fixed #964 for 2-player
// was hardcoded to two seats in THREE independent ways, in each of TWO near-identical blocks
// (theirHand and theirResources) in zzGameCodeGenerator.php:
//   (1) the decision-queue scan only ran `if($vSeat === 1 || $vSeat === 2)`;
//   (2) it matched the LITERAL 'theirHand'/'theirResources', but an N-player decision Param carries
//       'p3Hand-0'/'p3Resources-0', which matches neither;
//   (3) only $canSeeHandPlayer1/2 and $canSeeResourcesPlayer1/2 ever carried a reveal term at all.
//
// ⚠ WHY THIS TEST EXISTS AT ALL: the schema regression suite is STRUCTURALLY BLIND here — it renders no
// transport, so all ~17 SWULookAtOpponentHand cards can be perfectly seat-correct, go green, and still
// show a seat-3 player a row of card backs. A green suite is not evidence for this change.
//
// It asserts the REAL emitted text: the scanner is extracted from the GENERATED GetNextTurn.php and run,
// so a generator change that breaks it fails here rather than in a live game.
function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } echo "  ok: $msg\n"; }

$root = __DIR__ . '/../../..';

// ── 1. The GENERATOR is the tracked source (GetNextTurn.php is gitignored and regenerated) ──────────
$gen = file_get_contents($root . '/zzGameCodeGenerator.php');
check($gen !== false, 'zzGameCodeGenerator.php is readable');
$genCode = preg_replace('~^\s*//[^\n]*~m', '', $gen);            // assert CODE, not comments
check(strpos($genCode, '$_swuRevealSeats') !== false,
      'the generator emits the per-seat reveal scanner');
check(strpos($genCode, 'viewerLooksAtOppHand') === false
   && strpos($genCode, 'viewerLooksAtOppResources') === false,
      'the two-seat boolean flags are GONE from the generator (not merely unused)');

// ── 2. The regenerated transport carries it, for EVERY seat ─────────────────────────────────────────
$ntp = $root . '/SWUSim/GetNextTurn.php';
$nt  = file_get_contents($ntp);
check($nt !== false, 'SWUSim/GetNextTurn.php is readable (run zzGameCodeGenerator.php rootName=SWUSim)');
foreach ([1, 2, 3, 4] as $seat) {
    check(strpos($nt, "\$canSeeHandPlayer{$seat} = \$canSeePrivatePlayer{$seat} || \$spectatorCanSeeHands || !empty(\$viewerLooksAtHandSeats[{$seat}]);") !== false,
          "seat {$seat}'s HAND flag carries a reveal term");
    check(strpos($nt, "\$canSeeResourcesPlayer{$seat} = \$canSeePrivatePlayer{$seat} || !empty(\$viewerLooksAtResourceSeats[{$seat}]);") !== false,
          "seat {$seat}'s RESOURCES flag carries a reveal term");
}
check(strpos($nt, 'if($vSeat === 1 || $vSeat === 2)') === false,
      'the seat-1-or-2 scan gate is gone from the generated transport');

// ── 3. Extract the EMITTED scanner and actually run it ───────────────────────────────────────────────
$start = strpos($nt, '$_swuRevealSeats = function');
$end   = strpos($nt, '$viewerLooksAtHandSeats');
check($start !== false && $end !== false && $end > $start, 'the emitted scanner can be located');
$scannerSrc = substr($nt, $start, $end - $start);

$GLOBALS['__seatCount'] = 2;
$GLOBALS['__queues']    = [];
function SeatCountForGame(): int { return $GLOBALS['__seatCount']; }
function GetDecisionQueue($seat) { return $GLOBALS['__queues'][$seat] ?? []; }
function OpponentsOf($seat) {
    $out = [];
    for ($i = 1; $i <= $GLOBALS['__seatCount']; ++$i) if ($i !== $seat) $out[] = $i;
    return $out;
}
function dq(string $param, bool $removed = false) {
    $o = new stdClass(); $o->Param = $param; if ($removed) $o->removed = true; return $o;
}
eval($scannerSrc);
check(is_callable($_swuRevealSeats), 'the emitted scanner evaluates to a callable');

$seats = function(array $m) { $k = array_keys($m); sort($k); return $k; };

// ── 4. I1 — TWO SEATS MUST BE BYTE-IDENTICAL TO THE OLD BEHAVIOUR ───────────────────────────────────
$GLOBALS['__seatCount'] = 2;
$GLOBALS['__queues'] = [1 => [dq('DISCARD_FROM_OPP_HAND|theirHand-0')]];
check($seats($_swuRevealSeats(1, 'Hand')) === [2], '2P: seat-1 viewer looking at theirHand reveals seat 2');
$GLOBALS['__queues'] = [2 => [dq('theirHand-0')]];
check($seats($_swuRevealSeats(2, 'Hand')) === [1], '2P: seat-2 viewer looking at theirHand reveals seat 1');
$GLOBALS['__queues'] = [1 => [dq('theirResources-0')]];
check($seats($_swuRevealSeats(1, 'Resources')) === [2], '2P: theirResources reveals the one opponent');
$GLOBALS['__queues'] = [1 => [dq('theirHand-0')]];
check($_swuRevealSeats(0, 'Hand') === [], '2P: a SPECTATOR (seat 0) is revealed nothing');
$GLOBALS['__queues'] = [1 => [dq('myHand-0')]];
check($_swuRevealSeats(1, 'Hand') === [], '2P: a decision about your OWN hand reveals nothing extra');

// ── 5. FOUR SEATS — the three original hardcodes, each pinned ───────────────────────────────────────
$GLOBALS['__seatCount'] = 4;

// (1)+(3): a seat-3 VIEWER used to be skipped entirely, and seat flags 3/4 had no reveal term.
$GLOBALS['__queues'] = [3 => [dq('DISCARD_FROM_OPP_HAND|p1Hand-2')]];
check($seats($_swuRevealSeats(3, 'Hand')) === [1],
      '4P: a SEAT-3 viewer is scanned at all, and sees seat 1 (old code: skipped the scan entirely)');
$GLOBALS['__queues'] = [1 => [dq('p4Hand-0')]];
check($seats($_swuRevealSeats(1, 'Hand')) === [4],
      '4P: seat 4 CAN be revealed (old code: $canSeeHandPlayer4 had no reveal term at all)');

// (2): the N-player Param form.
$GLOBALS['__queues'] = [1 => [dq('SOME_HANDLER|p3Hand-0')]];
check($seats($_swuRevealSeats(1, 'Hand')) === [3],
      '4P: a p{n}Hand Param is matched (old code matched only the literal "theirHand")');
$GLOBALS['__queues'] = [1 => [dq('LAW_066|p3Resources-1')]];
check($seats($_swuRevealSeats(1, 'Resources')) === [3], '4P: p{n}Resources is matched too');

// Only the named seat — never a bystander.
$GLOBALS['__queues'] = [1 => [dq('p3Hand-0')]];
$r = $_swuRevealSeats(1, 'Hand');
check(empty($r[2]) && empty($r[4]), '4P: looking at seat 3 does NOT reveal seats 2 or 4');

// Multiple seats in one Param are all revealed.
$GLOBALS['__queues'] = [1 => [dq('p2Hand-0&p4Hand-1')]];
check($seats($_swuRevealSeats(1, 'Hand')) === [2, 4], '4P: a multi-seat Param reveals each named seat');

// Your own seat is never added, even if the Param names it.
$GLOBALS['__queues'] = [3 => [dq('p3Hand-0')]];
check($_swuRevealSeats(3, 'Hand') === [], '4P: a Param naming your OWN seat adds nothing');

// Resolved/removed decisions must not keep a hand open.
$GLOBALS['__queues'] = [1 => [dq('p3Hand-0', true)]];
check($_swuRevealSeats(1, 'Hand') === [], '4P: a REMOVED decision reveals nothing (auto-clears)');

// ⚠ Deliberate conservatism: above two seats "their" names no specific seat. A card still emitting the
// legacy form must be CONVERTED to p{n}<Zone>; the transport must not guess. Under-revealing is a blank
// row the player can report — over-revealing silently leaks a private zone to the whole table.
$GLOBALS['__queues'] = [1 => [dq('theirHand-0')]];
check($_swuRevealSeats(1, 'Hand') === [],
      '4P: a legacy theirHand Param reveals NOTHING rather than guessing (or leaking every opponent)');

// Zone names must not cross-match.
$GLOBALS['__queues'] = [1 => [dq('p3Resources-0')]];
check($_swuRevealSeats(1, 'Hand') === [], 'a Resources Param does not open a Hand');

echo "\nALL PASS — hidden-zone reveal is seat-correct for 1..4 and unchanged at two seats.\n";
