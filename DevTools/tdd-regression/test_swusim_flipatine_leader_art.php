<?php
// RUN VIA CLI (it calls Apache inside the container; never curl this file):
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swusim_flipatine_leader_art.php
//
// BUG REPORT (game 506505, 2026-09-17): "I used Chancellor Palpatine's ability and he exhausted, but
// didn't flip to Villainy side… I do get to heal and draw, but I don't get the Villainy side."
// The ENGINE was right (that game's P3 leader is Deployed=true, i.e. flipped, with the draw and heal
// applied) — the POLL PAYLOAD was wrong: the leader slot shipped the FRONT CardID, so the client drew
// the Heroism face forever.
//
// TWI_017 "Flipatine" is a double-leader-face flip card with NO unit side: its Deployed flag IS the
// flipped Villainy face, and the leader slot itself must show "TWI_017_back".
// SWULeaderDisplayCardID() (SWUSim/Custom/GameLogic.php) has always done this correctly, but the
// generator only wired it into the DisplayMode=="Single" + Public branch, while SWUSim's Leader zone is
// declared `Display: Visibility=Public, Mode=All` — so the call was NEVER EMITTED. A helper with no
// caller: the exact pattern the project memory warns about.
header('Content-Type: text/plain');
require_once __DIR__ . '/fixtures/swusim_chat_http_helpers.php';

$checks = [];

// Flipatine FLIPPED (the reported state): Deployed=true with no arena unit.
$FLIPPED = <<<'MD'
## GIVEN
CommonSetup: brk/bbw/{myLeader:TWI_017:1;myLeaderFlipped:true}
P1OnlyActions: true

## WHEN

## EXPECT
TURNPLAYER:1
MD;

// The same leader UNFLIPPED (the control): the slot must keep its FRONT art.
$FRONT = <<<'MD'
## GIVEN
CommonSetup: brk/bbw/{myLeader:TWI_017:1}
P1OnlyActions: true

## WHEN

## EXPECT
TURNPLAYER:1
MD;

// ⚠ Count the DISPLAY id only. A rendered card is "<displayID> <counters> {json}", and the json keeps
// the real state ("CardID":"TWI_017") — which is CORRECT and must not change. An early version of this
// test counted every "TWI_017" in the payload and so could never pass: the state json always matches.
function leader_art_ids(string $gn, string $seat): array
{
    $raw = swuchat_poll_full_raw($gn, $seat);
    preg_match_all('/(?:^|[>|\s])(TWI_017(?:_back)?)\s+\d/', $raw, $m);
    $front = 0; $back = 0;
    foreach ($m[1] as $hit) { if (substr($hit, -5) === '_back') $back++; else $front++; }
    return ['front' => $front, 'back' => $back, 'stateJson' => (strpos($raw, '"CardID":"TWI_017"') !== false)];
}

$gFlip = swuchat_make_game($FLIPPED);
$gFront = swuchat_make_game($FRONT);
$checks['fixtures created'] = $gFlip !== '' && $gFront !== '';

// ── the bug ──
$flip = leader_art_ids($gFlip, '1');
$checks['FLIPPED: own view ships the back face'] = $flip['back'] > 0;
$checks['FLIPPED: own view ships no front face'] = $flip['front'] === 0;
// The STATE must be untouched: only the display id flips, the stored CardID stays the leader-side id.
$checks['FLIPPED: state keeps the leader CardID'] = $flip['stateJson'] === true;
// The opponent must see the same face — the flip is public information.
$flipOpp = leader_art_ids($gFlip, '2');
$checks['FLIPPED: opponent sees the back face'] = $flipOpp['back'] > 0 && $flipOpp['front'] === 0;
// A spectator too.
$flipSpec = leader_art_ids($gFlip, 'S');
$checks['FLIPPED: spectator sees the back face'] = $flipSpec['back'] > 0 && $flipSpec['front'] === 0;

// ── the control: an UNflipped Flipatine keeps its front art ──
$front = leader_art_ids($gFront, '1');
$checks['UNFLIPPED: own view ships the front face'] = $front['front'] > 0;
$checks['UNFLIPPED: own view ships no back face']   = $front['back'] === 0;

$fail = 0;
foreach ($checks as $name => $ok) { echo ($ok ? 'PASS' : 'FAIL') . "  $name\n"; if (!$ok) $fail++; }
echo "games: flipped=$gFlip front=$gFront  counts flipped=" . json_encode($flip) . " front=" . json_encode($front) . "\n";
echo $fail === 0 ? "\nPASS (" . count($checks) . ")\n" : "\nFAIL ($fail of " . count($checks) . ")\n";
exit($fail === 0 ? 0 : 1);
