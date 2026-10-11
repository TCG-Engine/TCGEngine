<?php
// A replay viewer game refuses every engine input and shows the file's stated power/HP/keywords.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swupgn_viewer_game.php
require_once __DIR__ . '/fixtures/swupgn_test_helpers.php';
restore_error_handler();
error_reporting(E_ALL & ~E_DEPRECATED);
chdir(__DIR__ . '/../..');
if (!function_exists('ConvertMzIDToAbsolute')) { function ConvertMzIDToAbsolute($m, $p): string { return ''; } }
foreach (['DeterministicRNG', 'CoreZoneModifiers', 'NetworkingLibraries'] as $f) include_once "./Core/$f.php";
include_once './SWUSim/ZoneClasses.php'; include_once './SWUSim/ZoneAccessors.php';
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php'; include_once './SWUSim/GamestateParser.php';
include_once './SWUSim/TurnController.php'; include_once './SWUSim/Custom/GameLogic.php'; include_once './SWUSim/Custom/CombatLogic.php';
require_once './SWUSim/Custom/SwuPgnBoard.php';
include_once './AppCore/SWU/CardDisplayID.php';   // SWUDisplayCardID, as GetNextTurn.php loads it

$doc = SwuPgnParse(file_get_contents('./DevTools/tdd-regression/fixtures/swupgn/viewer-game.swupgn'));
// Stated stats the engine could never compute for these cards, so the check proves the override is read.
$final = SwuPgnFold($doc['events']);
foreach ($final['players'][1]['cards'] as $i => $c) if ($c['id'] === 'JTL#095') { $final['players'][1]['cards'][$i]['power'] = 9; $final['players'][1]['cards'][$i]['hp'] = 7; }
foreach ($final['players'][2]['cards'] as $i => $c) if ($c['id'] === 'SOR#045') $final['players'][2]['cards'][$i]['keywords'] = ['sentinel'];   // printed Yoda has Restore, not Sentinel
InitializeGamestate();
SwuPgnBoardWrite($final, $doc, 'both');
$wing = null; foreach (GetSpaceArena(1) as $o) if ($o->CardID === 'JTL_095') $wing = $o;
$yoda = null; foreach (GetGroundArena(2) as $o) if ($o->CardID === 'SOR_045') $yoda = $o;

SwuPgnTestCheck(function_exists('SwuPgnIsViewerGame') && SwuPgnIsViewerGame(), 'GameLogic loads the viewer helpers and sees the flag');
SwuPgnTestEq([ObjectPowerBadgeValue($wing), ObjectHPBadgeValue($wing)], [9, 7], 'power/HP badges show the stated values');
SwuPgnTestEq([ObjectHasRestore($yoda), ObjectHasSentinel($yoda)], [0, 1], 'keyword badges follow the STATED keywords, not the printed ones');
SwuPgnTestEq([ObjectHiddenUnattackable($yoda), ObjectCoordinateActive($yoda), ObjectCoordinateInactive($yoda)], [0, 0, 0], 'derived indicators are off in a viewer game');
$v = GameValidateEngineAction(['mode' => 10002, 'playerID' => 1]);
SwuPgnTestCheck(is_array($v) && $v['allowed'] === false, 'every engine input is refused in a viewer game');
$ph = null; foreach (GetGroundArena(2) as $o) if (str_starts_with($o->CardID, 'SWUPGNX_')) $ph = $o;
$raised = [];
set_error_handler(function ($no, $str) use (&$raised) { $raised[] = $str; return true; }, E_ALL);
ob_start(); $display = $ph ? SWUArenaDisplayCardID($ph) : null; $noise = ob_get_clean();
restore_error_handler();
SwuPgnTestCheck($ph !== null && $display === $ph->CardID && $noise === '' && $raised === [], 'an unknown CardID displays as itself without a PHP notice', $raised);

// A normal game is untouched.
InitializeGamestate();
AddGroundArena(1, 'JTL_095', 1, 1, 0, 1, '-', '-', 1);
$plain = GetGroundArena(1)[0];
SwuPgnTestCheck(!SwuPgnIsViewerGame(), 'a normal game is not a viewer game');
SwuPgnTestEq(ObjectPowerBadgeValue($plain), ObjectCurrentPower($plain), 'a normal game computes power as before');
SwuPgnTestEq(ObjectHPBadgeValue($plain), ObjectCurrentHP($plain), 'a normal game computes HP as before');
SwuPgnTestEq(GameValidateEngineAction(['mode' => 10002])['allowed'], true, 'a normal game allows input');

SwuPgnTestFinish();
