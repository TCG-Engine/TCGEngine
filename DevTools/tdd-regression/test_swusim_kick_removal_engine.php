<?php
// RUN VIA CLI:
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swusim_kick_removal_engine.php
//
// Inactivity kick (stage 2) — SWUApplyKick itself, IN PROCESS on a real gamestate. This is the home of
// the "NOBODY HEALS" assertion (user decision 2026-09-17): the end-to-end flow test can see that a seat
// left the game, but not that no base was healed.
// Spec: docs/superpowers/specs/2026-09-17-swusim-inactivity-timer-and-kick-design.md
header('Content-Type: text/plain');
require_once __DIR__ . '/fixtures/swusim_chat_http_helpers.php';

$checks = [];

// Build a Twin Suns board where every base carries damage, so a heal would be visible.
$SCHEMA = <<<'MD'
## GIVEN
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010; myBaseDamage:9; theirBaseDamage:7}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithGamePhase: ActionPhase
WithActivePlayer: 1
#// ⚠ seat 2's damage comes from theirBaseDamage above — WithP2Base: SOR_026:7 silently drops it.
WithP3Base: SOR_026:5
WithP4Base: SOR_026:8

## WHEN

## EXPECT
TURNPLAYER:1
MD;

$gameNameArg = swuchat_make_game($SCHEMA);
$checks['fixture created'] = $gameNameArg !== '';
if ($gameNameArg === '') { echo "FAIL  fixture created\n\nFAIL (1 of 1)\n"; exit(1); }

// Load the engine in this process and drive the removal directly.
chdir(__DIR__ . '/../../SWUSim');
include './GamestateParser.php';
include './ZoneAccessors.php';
include './ZoneClasses.php';
include '../SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
$GLOBALS['gameName'] = $gameNameArg;
global $gameName;
$gameName = $gameNameArg;
ParseGamestate('./');

$baseDamage = function (int $seat): int {
    $b = &GetBase($seat);
    return isset($b[0]) ? intval($b[0]->Damage) : -1;
};
$before = [1 => $baseDamage(1), 2 => $baseDamage(2), 3 => $baseDamage(3), 4 => $baseDamage(4)];
$checks['fixture bases carry damage'] = $before[2] > 0 && $before[3] > 0 && $before[4] > 0;
$checks['four live seats to start']    = GetLiveSeatsArray() === [1, 2, 3, 4];

SWUApplyKick(1);          // Twin Suns free-for-all: seat 1 is removed

$after = [1 => $baseDamage(1), 2 => $baseDamage(2), 3 => $baseDamage(3), 4 => $baseDamage(4)];
$checks['kicked seat left LiveSeats'] = !in_array(1, GetLiveSeatsArray(), true);
$checks['other seats still live']     = GetLiveSeatsArray() === [2, 3, 4];
$checks['NOBODY healed (seat 2)']     = $after[2] === $before[2];
$checks['NOBODY healed (seat 3)']     = $after[3] === $before[3];
$checks['NOBODY healed (seat 4)']     = $after[4] === $before[4];
$checks['game did not end']           = DecisionQueueController::GetVariable('GAMEOVER_WINNER') === null;
$checks['kicked seat queue drained']  = empty(GetDecisionQueue(1));   // or the table soft-locks
$checks['removal is logged']          = strpos(strval(GetGameLog()), 'removed for inactivity') !== false;

// ── A KICK MUST NOT COUNT TOWARD THE TWIN SUNS WIN CONDITION (owner ruling, 2026-09-26) ──────────
// CR 12.7.1 ends the game at the next phase boundary once a player is ELIMINATED, scoring by highest
// remaining base HP. An inactivity kick routed straight through SWUEliminateSeat, so being kicked
// armed that ending and the next player to finish the phase won outright — reported on game 1311538,
// where P2 was kicked, P3 later took the Blast counter, and the game ended on the spot.
//
// ⚠ THE 'game did not end' CHECK ABOVE CANNOT CATCH THIS. Scoring is DEFERRED to the phase boundary,
// so GAMEOVER_WINNER is legitimately still null the instant after the kick — that assertion passed
// throughout the bug's life. The arming flag is the thing to assert, plus the boundary itself.
$checks['kick does NOT arm the end-game']   = GetSWUVar('SWU_TS_GAME_ENDING') !== '1';
_SWUScoreTwinSunsEndOfPhase();
$checks['no winner at the phase boundary']  = DecisionQueueController::GetVariable('GAMEOVER_WINNER') === null;
$checks['survivors still live after it']    = GetLiveSeatsArray() === [2, 3, 4];

// ── …BUT A LAST PLAYER STANDING HAS STILL WON ───────────────────────────────────────────────────
// The other half of the ruling, and the branch the fix adds: not arming the end-game must not leave
// a table that can NEVER end. Kick everyone but seat 4 and the game has to score, with the survivor
// winning — base HP is irrelevant when only one seat is left (seat 4 carries the MOST damage of the
// three here, 8, so a scoring path that still ranked by HP would pick someone else).
SWUApplyKick(2);
SWUApplyKick(3);
$checks['kicks down to one survivor']       = GetLiveSeatsArray() === [4];
$checks['last player standing wins']        = DecisionQueueController::GetVariable('GAMEOVER_WINNER') === '4';

$fail = 0;
foreach ($checks as $name => $ok) { echo ($ok ? 'PASS' : 'FAIL') . "  $name\n"; if (!$ok) $fail++; }
echo "game=$gameNameArg bases before=" . json_encode($before) . " after=" . json_encode($after) . "\n";
echo $fail === 0 ? "\nPASS (" . count($checks) . ")\n" : "\nFAIL ($fail of " . count($checks) . ")\n";
exit($fail === 0 ? 0 : 1);
