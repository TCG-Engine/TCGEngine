<?php
// TDD guard: TEMPORARY effects survive a snapshot round-trip — both undo and bookmark load.
//
// WHY THIS FILE EXISTS: temporary state is where snapshot/restore features classically leak. A
// snapshot that captures the obvious fields (CardID, damage, status) but drops a phase buff, a granted
// keyword, or a discard-pile play permission restores a board that LOOKS right and plays wrong. This
// codebase already had one incident of exactly that shape: an arena move stringified a unit's
// TurnEffects array to the literal "Array", silently wiping phase buffs, granted keywords and delayed
// markers.
//
// Both DIRECTIONS are tested, because they fail independently:
//   PRESERVE — an effect present when the snapshot was taken must come back.
//   REVERT   — an effect applied AFTER the snapshot must be GONE once it is restored.
// A field that is serialized but never restored passes "preserve" only by accident (the live value was
// never cleared); a field that is restored but never serialized passes "revert" only by accident.
//
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_snapshot_temporary_effects.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
chdir('/var/www/html/TCGEngine');
if (!function_exists('ConvertMzIDToAbsolute'))      { function ConvertMzIDToAbsolute($m,$p):string{return '';} }
if (!function_exists('QueueDamageAnimation'))       { function QueueDamageAnimation($t,$a):void{} }
if (!function_exists('QueueRestoreAnimation'))      { function QueueRestoreAnimation($t,$a):void{} }
if (!function_exists('QueuePreventedDamageAnimation')) { function QueuePreventedDamageAnimation($t):void{} }
if (!function_exists('QueueShieldBreakAnimation'))  { function QueueShieldBreakAnimation($t):void{} }
foreach (['DeterministicRNG','CoreZoneModifiers','GameAuth'] as $f) include_once "./Core/$f.php";
include_once './SWUSim/ZoneClasses.php'; include_once './SWUSim/ZoneAccessors.php';
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php'; include_once './SWUSim/GamestateParser.php';
foreach (['Assertions','Cards','CommonSetup','GameStateBuilder','GameTestAdapter','SchemaTestRunner','TestRunner'] as $f) include_once "./SWUSim/Tests/Framework/$f.php";
global $gameName, $playerID;
$gameName = 'tmpfx_' . getmypid(); $playerID = 1;
@mkdir('./Games/' . $gameName, 0777, true);

// SOR_095 Battlefield Marine (3/3) on P1's ground — the buff/debuff subject.
$b = new GameStateBuilder(); CommonSetup($b, 'grw', 'brk', [], []); $b->WithActivePlayer(1);
$b->WithGroundUnitForPlayer(1, 'SOR_095');
$g = new GameTestAdapter(); $g->loadState($b);
ob_start(); AutoAdvanceAndExecute(); ob_end_clean();
$GLOBALS['SWU_TEST_FORCE_PRIVATE'] = true;

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

$unit    = function () { return GetZoneObject('myGroundArena-0'); };
$effects = function () use ($unit) { $u = $unit(); return is_array($u->TurnEffects ?? null) ? $u->TurnEffects : []; };
$hasFx   = function ($needle) use ($effects) {
    foreach ($effects() as $e) { if (strpos((string)$e, $needle) !== false) return true; }
    return false;
};
$power   = function () use ($unit) { return ObjectCurrentPower($unit()); };
$hp      = function () use ($unit) { return ObjectCurrentHP($unit()); };

UndoStackClear(); BookmarkStoreClear(); UndoCursorSet(-1);

$basePower = $power(); $baseHp = $hp();
$check($basePower === 3 && $baseHp === 3, "baseline 3/3 (got {$basePower}/{$baseHp})");

// ═══ 1. A phase buff present AT SNAPSHOT TIME must come back ════════════════════════════════
SWUApplyPhaseBuff('myGroundArena-0', 2, 2, 'TESTSRC');
$check($power() === 5 && $hp() === 5, 'buff applied: 5/5 (got ' . $power() . '/' . $hp() . ')');
PushUndoSnapshot(1, 'action');                       // ord0 — snapshot WITH the buff
ob_start(); SWUTakeBookmark(1, 'buffed', '', $gameName); ob_end_clean();   // bookmark WITH the buff

// Mutate away from it: add a SECOND buff so the live state differs from the snapshot.
SWUApplyPhaseBuff('myGroundArena-0', 1, 1, 'TESTSRC2');
$check($power() === 6, 'second buff stacks: 6/6 (got ' . $power() . '/' . $hp() . ')');

// Undo → back to the single-buff state. Not baseline: the FIRST buff must still be there.
ob_start(); SWUDoUndo(1, 'step'); ob_end_clean();
$check($power() === 5 && $hp() === 5, 'UNDO preserved the first buff and reverted the second (got ' . $power() . '/' . $hp() . ')');
// Token shape is "{base}-{power}-{hp}^N" (see SWUApplyPhaseBuff / _SWUStackingStatToken); an ad-hoc
// source label is NOT embedded unless it is a registered $turnEffectRegistry key, so probe the numbers.
$check($hasFx('SWUBUFF-2-2') && !$hasFx('SWUBUFF-1-1'), 'TurnEffects: +2/+2 present, +1/+1 gone');

// ═══ 2. REVERT — an effect applied AFTER the bookmark must be GONE on load ══════════════════
SWUApplyPhaseDebuff('myGroundArena-0', 1, 0, 'LATEDEBUFF');
$check($hasFx('SWUDEBUFF-1-0'), 'late debuff applied');
ob_start(); SWULoadBookmark(1, 1, '', $gameName); ob_end_clean();
$check(!$hasFx('SWUDEBUFF-1-0'), 'BOOKMARK LOAD reverted the later debuff (it must NOT persist)');
$check($power() === 5 && $hp() === 5, 'bookmark restored the buffed 5/5 (got ' . $power() . '/' . $hp() . ')');
$check($hasFx('SWUBUFF-2-2'), 'the bookmarked buff itself survived the load');

// ═══ 3. Granted KEYWORD (a TurnEffect, not a stat change) ═══════════════════════════════════
// A keyword grant is stored the same way but read by a different code path, so a serializer that
// mangles the array (the "Array" stringification incident) breaks it independently of the stat maths.
UndoStackClear(); BookmarkStoreClear(); UndoCursorSet(-1);
AddTurnEffect('myGroundArena-0', 'SWU_GRANT_KEYWORD-Sentinel');
$check($hasFx('Sentinel'), 'keyword grant applied');
PushUndoSnapshot(1, 'action');
ob_start(); SWUTakeBookmark(1, 'sentinel', '', $gameName); ob_end_clean();
AddTurnEffect('myGroundArena-0', 'SWU_GRANT_KEYWORD-Overwhelm');
$check($hasFx('Overwhelm'), 'second keyword applied');
ob_start(); SWULoadBookmark(1, 1, '', $gameName); ob_end_clean();
$check($hasFx('Sentinel'), 'granted keyword SURVIVED the bookmark load');
$check(!$hasFx('Overwhelm'), 'the later keyword was reverted by the load');
$check(count($effects()) > 0, 'TurnEffects is a real array after restore, not the string "Array"');
$raw = $unit()->TurnEffects;
$check(is_array($raw) && !in_array('Array', $raw, true), 'TurnEffects contains no literal "Array" element');

// ═══ 4. Discard-pile play permission — the Stolen AT-Hauler (JTL_221) case ══════════════════
// "For this phase, an opponent may play this unit from its owner's discard pile for free" is stored as
// a Modifier on the DISCARD entry (OTPF). It is temporary, it lives on a zone most snapshot code never
// thinks about, and losing it silently removes a play the opponent is entitled to.
UndoStackClear(); BookmarkStoreClear(); UndoCursorSet(-1);
$dIdx = count(GetDiscard(1));
AddDiscard(1, 'JTL_221', 'PLAY');
$dEntry = GetZoneObject('myDiscard-' . $dIdx);
$dEntry->Modifier = 'OTPF';                       // exactly what $cardDiscardedHandlers['JTL_221:0'] sets
$mod = function () use ($dIdx) { $o = GetZoneObject('myDiscard-' . $dIdx); return (string)($o->Modifier ?? ''); };
$check($mod() === 'OTPF', 'OTPF stamped on the discard entry');

PushUndoSnapshot(1, 'action');
ob_start(); SWUTakeBookmark(1, 'athauler', '', $gameName); ob_end_clean();

// Clear it, as RegroupPhaseStart would, then restore.
$dEntry = GetZoneObject('myDiscard-' . $dIdx); $dEntry->Modifier = '';
$check($mod() === '', 'modifier cleared');
ob_start(); SWUDoUndo(1, 'step'); ob_end_clean();
$check($mod() === 'OTPF', 'UNDO restored the OTPF discard modifier (Stolen AT-Hauler stays playable)');

$dEntry = GetZoneObject('myDiscard-' . $dIdx); $dEntry->Modifier = '';
ob_start(); SWULoadBookmark(1, 1, '', $gameName); ob_end_clean();
$check($mod() === 'OTPF', 'BOOKMARK LOAD restored the OTPF discard modifier');

// And the REVERT direction: a modifier stamped after the bookmark must not survive the load.
$dEntry = GetZoneObject('myDiscard-' . $dIdx); $dEntry->Modifier = 'TPF';
ob_start(); SWULoadBookmark(1, 1, '', $gameName); ob_end_clean();
$check($mod() === 'OTPF', 'a LATER modifier change was reverted to the bookmarked OTPF (got "' . $mod() . '")');

// ═══ 5. GlobalEffects (phase-scoped game flags) ═════════════════════════════════════════════
UndoStackClear(); BookmarkStoreClear(); UndoCursorSet(-1);
AddGlobalEffects(1, 'SWU_TEST_TEMP_FLAG');
$check(GlobalEffectCount(1, 'SWU_TEST_TEMP_FLAG') > 0, 'global effect applied');
ob_start(); SWUTakeBookmark(1, 'globals', '', $gameName); ob_end_clean();
RemoveGlobalEffect(1, 'SWU_TEST_TEMP_FLAG');
$check(GlobalEffectCount(1, 'SWU_TEST_TEMP_FLAG') === 0, 'global effect removed');
ob_start(); SWULoadBookmark(1, 1, '', $gameName); ob_end_clean();
$check(GlobalEffectCount(1, 'SWU_TEST_TEMP_FLAG') > 0, 'BOOKMARK LOAD restored the global effect');

// ═══ 6. Subcards (shields / experience) and damage ══════════════════════════════════════════
UndoStackClear(); BookmarkStoreClear(); UndoCursorSet(-1);
$u = $unit(); $u->Damage = 2;
$u->Subcards[] = 'SOR_T02';                             // Shield token, attached directly
$subCount = function () use ($unit) { $u = $unit(); return is_array($u->Subcards ?? null) ? count($u->Subcards) : 0; };
$withShield = $subCount();
$check($withShield > 0, 'shield subcard attached (' . $withShield . ')');
ob_start(); SWUTakeBookmark(1, 'shielded', '', $gameName); ob_end_clean();

$u = $unit(); $u->Damage = 0; $u->Subcards = [];
$check($subCount() === 0, 'subcards cleared');
ob_start(); SWULoadBookmark(1, 1, '', $gameName); ob_end_clean();
$check($subCount() === $withShield, 'BOOKMARK LOAD restored the shield subcard (got ' . $subCount() . ')');
$check(intval($unit()->Damage) === 2, 'damage restored to 2 (got ' . intval($unit()->Damage) . ')');

UndoStackClear(); BookmarkStoreClear();
array_map('unlink', glob('./Games/' . $gameName . '/*') ?: []); @rmdir('./Games/' . $gameName);
echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
