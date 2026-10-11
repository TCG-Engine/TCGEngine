<?php
// Reader board → SWUSim gamestate (replay viewer design, "Board conversion").
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swupgn_board.php
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

global $gameName;
$gameName = 'swupgnboard' . getmypid();
@mkdir("./SWUSim/Games/$gameName", 0777, true);
$doc = SwuPgnParse(file_get_contents('./DevTools/tdd-regression/fixtures/swupgn/viewer-game.swupgn'));
$final = SwuPgnFold($doc['events']);
$write = function (array $state, string $view) use ($doc) { InitializeGamestate(); return SwuPgnBoardWrite($state, $doc, $view); };
$unit = function (int $p, string $cardID) { foreach (array_merge(GetGroundArena($p), GetSpaceArena($p)) as $o) if ($o->CardID === $cardID) return $o; return null; };
$subs = fn($o) => is_array($o->Subcards ?? null) ? $o->Subcards : [];
$sv = fn($s, string $k) => is_array($s) ? ($s[$k] ?? null) : ($s->$k ?? null);
$subIds = fn($o) => array_map(fn($s) => $sv($s, 'CardID'), $subs($o));

$res = $write($final, 'both');
$inf = $unit(1, 'SOR_108');
SwuPgnTestCheck($inf !== null && $inf->Status == 0 && $inf->Damage == 0, 'a ground unit lands with Status (exhausted → 0) and Damage');
$ids = $subIds($inf); sort($ids);
SwuPgnTestEq($ids, ['ASH_T02', 'LOF_091', 'SOR_T02'], 'its printed upgrade, Shield and Advantage become subcards');
$wing = $unit(1, 'JTL_095');
$pilot = array_values(array_filter($subs($wing), fn($s) => $sv($s, 'CardID') === 'JTL_058'))[0] ?? null;
SwuPgnTestCheck($wing !== null && $pilot !== null && !empty($sv($pilot, 'IsPilot')), 'a pilot is an IsPilot subcard on its vehicle');
$vader = $unit(1, 'SOR_010');
$leader = GetLeader(1)[0];
SwuPgnTestCheck($vader !== null && $leader->Deployed && $leader->DeployedUniqueID == $vader->UniqueID && $leader->EpicActionUsed, 'a deployed leader links to its arena unit and keeps Epic Action used');
$cap = array_values(array_filter($subs($vader), fn($s) => !empty($sv($s, 'IsCaptive'))));
SwuPgnTestCheck(count($cap) === 1 && $sv($cap[0], 'CardID') === 'JTL_032' && $sv($cap[0], 'Owner') == 2, 'a captive is a captive subcard owned by the other seat');
$yoda = $unit(2, 'SOR_045');
SwuPgnTestEq($subIds($yoda), ['SOR_T01'], 'Experience becomes an Experience subcard');
$ph = SwuPgnPlaceholderId('ZZZ#999');
SwuPgnTestCheck($unit(2, $ph) !== null && ($res['placeholders'][$ph] ?? null) === 'Mystery <b>Card</b>', 'an unknown card is a placeholder carrying its (raw, unescaped) name');
SwuPgnTestEq(GetBase(2)[0]->Damage, CardHp('SOR_028') - $final['players'][2]['baseHp'], 'base damage = printed HP − remaining');
$res1 = GetResources(1);
SwuPgnTestEq(count(array_filter($res1, fn($r) => $r->CardID === 'LAW_T01')), $final['players'][1]['credits'], 'credits become LAW_T01 resources');
SwuPgnTestEq(count(array_filter($res1, fn($r) => $r->CardID !== 'LAW_T01')), $final['players'][1]['resourcesReady'] + $final['players'][1]['resourcesExhausted'], 'one resource entry per counted resource');
SwuPgnTestEq(count(array_filter($res1, fn($r) => $r->CardID !== 'LAW_T01' && $r->Status == 0)), $final['players'][1]['resourcesExhausted'], 'the first N resources are exhausted');
SwuPgnTestCheck($final['players'][1]['resourcesExhausted'] > 0, 'the fixture has exhausted resources at the end (so the check above is not vacuous)');
SwuPgnTestCheck(in_array('SWU_HAS_FORCE', array_map(fn($g) => $g->CardID, GetGlobalEffects(2)), true), 'the Force is the SWU_HAS_FORCE global effect');
SwuPgnTestEq(count(GetHand(2)), $final['players'][2]['handSize'], 'hand count = handSize');
SwuPgnTestEq(count(array_filter(GetHand(2), fn($h) => $h->CardID === 'CardBack')), 0, "view 'both': both hands face up");
SwuPgnTestEq(GetSWUVar('SWUPGN_VIEWER'), '1', 'the viewer flag is set');
$stats = json_decode(GetSWUVar('SWUPGN_STATS'), true);
SwuPgnTestEq($stats[$wing->UniqueID]['p'] ?? null, 4, 'stated power is recorded per UniqueID');
SwuPgnTestEq(GetSWUVar('SWUPGN_NAMES', 'unset'), 'unset', 'placeholder names stay OUT of the gamestate (the poll payload is split on <~>; the client gets names from meta)');
// Keywords are file text that lands in the gamestate: only plain keyword words (with an optional number) survive.
$kw = $final; $kw['players'][2]['cards'][0]['keywords'] = ['Raid 2', 'sentinel', 'x<~>y', 'a"b', 'restore 1 2'];
$write($kw, 'both');
$kwStats = json_decode(GetSWUVar('SWUPGN_STATS'), true);
SwuPgnTestEq(array_values(array_filter(array_map(fn($s) => $s['k'] ?? null, $kwStats)))[0] ?? null, ['raid 2', 'sentinel'], 'stated keywords keep only "word" / "word N"; other text is dropped');
SwuPgnTestCheck(!preg_match('/[<>&\']|\\\\"/', GetSWUVar('SWUPGN_STATS')), 'the stats SWUVar carries no markup, delimiter or escaped-quote characters');

$write($final, 'p1');
SwuPgnTestEq(count(array_filter(GetHand(2), fn($h) => $h->CardID !== 'CardBack')), 0, "view 'p1': P2's hand is all card backs");
SwuPgnTestCheck(count(array_filter(GetHand(1), fn($h) => $h->CardID !== 'CardBack')) > 0, "view 'p1': P1's hand stays face up");

// A hand larger than the cards the file names → the rest are card backs.
$partial = $final; $partial['players'][1]['hand'] = array_slice($partial['players'][1]['hand'], 0, 1);
$write($partial, 'both');
SwuPgnTestEq(count(array_filter(GetHand(1), fn($h) => $h->CardID === 'CardBack')), $final['players'][1]['handSize'] - 1, 'unnamed hand cards are card backs');

// Before the first keyframe: no base damage, deck from DECKS totals (the viewer's timeline, SwuPgnBoardTimeline).
$early = SwuPgnTimelineStateAt(SwuPgnBoardTimeline($doc), 3);
$write($early, 'both');
SwuPgnTestEq(GetBase(1)[0]->Damage, 0, 'setup: base undamaged');
SwuPgnTestEq(count(GetDeck(1)), 8 - $early['players'][1]['handSize'], 'setup: deck = DECKS total − cards drawn so far');
// No event carries a base's starting HP: the fold's placeholder is 30. A 33-HP base (Data Vault) must not read 3 damage.
$bigBase = $doc; $bigBase['headers']['P1Base'] = 'JTL#024';
InitializeGamestate(); SwuPgnBoardWrite(SwuPgnTimelineStateAt(SwuPgnBoardTimeline($bigBase), 3), $bigBase, 'both');
SwuPgnTestEq(GetBase(1)[0]->Damage, 0, 'setup: a base with more than 30 HP is not damaged by the placeholder');

// The start state comes from card data: printed base HP, DECKS totals.
$startOf = function (array $headers, ?array $decks = null) use ($doc) { $d = $doc; $d['headers'] = $headers + $d['headers']; if ($decks !== null) $d['decks'] = $decks; return SwuPgnBoardStartState($d)['players']; };
$sp = $startOf(['P1Base' => 'JTL#021', 'P2Base' => 'JTL#031']);
SwuPgnTestEq([$sp[1]['baseHp'], $sp[1]['baseMaxHp'], $sp[2]['baseHp'], $sp[2]['baseMaxHp']], [CardHp('JTL_021'), CardHp('JTL_021'), CardHp('JTL_031'), CardHp('JTL_031')], 'start: Colossus / Lake Country start at their printed HP');
SwuPgnTestCheck(CardHp('JTL_021') > 30 && CardHp('JTL_031') > 30 && CardHp('JTL_024') > 30, 'Colossus, Lake Country and Data Vault are all over 30 HP (so these checks are not vacuous)');
SwuPgnTestEq(SwuPgnBoardStartState($bigBase)['players'][1]['baseHp'], CardHp('JTL_024'), 'start: Data Vault starts at its printed HP');
SwuPgnTestEq([$sp[1]['deckSize'] ?? null, $sp[2]['deckSize'] ?? null], [8, 8], 'start: deck size = the DECKS total');
$unk = $startOf(['P1Base' => 'ZZZ#777']);
SwuPgnTestCheck($unk[1]['baseHp'] === 30 && $unk[1]['baseMaxHp'] === 30, 'start: a base without card data keeps the placeholder');
SwuPgnTestCheck(!array_key_exists('deckSize', $startOf([], [])[1]), 'start: no DECKS section → deck size stays unknown');

// The file's own numbers win over card data (a keyframe states the base's max HP).
$stated = $final; $stated['round'] = 0; $stated['players'][1]['baseMaxHp'] = CardHp('SOR_020') + 10; $stated['players'][1]['baseHp'] = CardHp('SOR_020') + 8;
$write($stated, 'both');
SwuPgnTestEq(GetBase(1)[0]->Damage, 2, 'a keyframe\'s stated max HP wins over the printed HP — in any round, setup included');

// A file with NO keyframes, Colossus vs Lake Country: walk every step.
$noKf = [];
foreach (explode("\n", str_replace(['SOR#020', 'SOR#028'], ['JTL#021', 'JTL#031'], file_get_contents('./DevTools/tdd-regression/fixtures/swupgn/viewer-game.swupgn'))) as $l) {
    $j = json_decode($l, true);
    if (is_array($j) && isset($j['keyframe'])) { unset($j['keyframe']); $l = json_encode($j, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); }
    $noKf[] = $l;
}
$nk = SwuPgnParse(implode("\n", $noKf));
SwuPgnTestEq(array_values(array_filter($nk['events'], fn($e) => isset($e['keyframe']))), [], 'the no-keyframe variant has no keyframes');
$nkTl = SwuPgnBoardTimeline($nk);
$hitAt = null; foreach ($nk['events'] as $i => $e) if (($e['t'] ?? '') === 'DAMAGE' && ($e['tgt'] ?? '') === 'base@2') { $hitAt = $i; $hitHp = $e['hp']; break; }
SwuPgnTestCheck($hitAt !== null, 'the fixture hits P2\'s base once (so the damage check below is not vacuous)');
$bad = [];
foreach (SwuPgnSteps($nk)['steps'] as $st) {
    InitializeGamestate(); SwuPgnBoardWrite(SwuPgnTimelineStateAt($nkTl, $st['pos']), $nk, 'both');
    $out = [1 => 0, 2 => 0];
    for ($i = 0; $i <= $st['pos']; $i++) { $e = $nk['events'][$i]; if (($e['t'] ?? '') === 'MOVE' && ($e['from'] ?? '') === 'deck' && in_array($e['p'] ?? 0, [1, 2], true)) $out[$e['p']]++; }
    $want = [GetBase(1)[0]->Damage === 0, GetBase(2)[0]->Damage === ($st['pos'] >= $hitAt ? CardHp('JTL_031') - $hitHp : 0),
        count(GetDeck(1)) === 8 - $out[1], count(GetDeck(2)) === 8 - $out[2]];
    if (in_array(false, $want, true)) $bad[] = "step {$st['n']}: dmg " . GetBase(1)[0]->Damage . '/' . GetBase(2)[0]->Damage . ' deck ' . count(GetDeck(1)) . '/' . count(GetDeck(2)) . " (want deck " . (8 - $out[1]) . '/' . (8 - $out[2]) . ')';
}
SwuPgnTestEq($bad, [], 'no keyframes: a >30-HP base shows no phantom damage, the hit base shows printed − remaining, and the deck = DECKS total − cards drawn (cards in play included) at EVERY step');

// Double-sided leader flip face bit; turn/phase/initiative.
$flip = SwuPgnEmptyState();
$flip['round'] = 3; $flip['phase'] = 'regroup'; $flip['initiative'] = 2; $flip['initiativeTaken'] = true;
$flip['players'][1]['leader'] = ['id' => 'TWI#017', 'deployed' => false, 'exhausted' => false, 'epicActionUsed' => false, 'onStartingSide' => false];
$write($flip, 'both');
SwuPgnTestCheck(GetLeader(1)[0]->CardID === 'TWI_017' && GetLeader(1)[0]->Deployed && GetLeader(1)[0]->DeployedUniqueID == 0, 'a flipped double-sided leader is Deployed with no arena link (face bit)');
global $gTurnNumber, $gCurrentPhase, $gInitiativeCounter;
SwuPgnTestEq([$gTurnNumber, $gCurrentPhase, $gInitiativeCounter], [3, 'RGS', 'P2_CLAIMED'], 'round, phase and initiative');

// Every top-level and per-card field the reader produces is handled.
$handled = SwuPgnBoardHandledFields();
$seen = [];
foreach ([$final, $flip] as $st) {
    foreach ($st as $k => $_) $seen["state.$k"] = true;
    foreach ($st['players'] as $ps) { foreach ($ps as $k => $_) $seen["player.$k"] = true; foreach ($ps['cards'] as $c) foreach ($c as $k => $_) $seen["card.$k"] = true; }
}
SwuPgnTestEq(array_values(array_diff(array_keys($seen), $handled)), [], 'no reader field is left unconverted');

// Round trip: write the gamestate file and read it back.
$write($final, 'both');
WriteGamestate('./SWUSim/');
InitializeGamestate();
ParseGamestate('./SWUSim/');
SwuPgnTestCheck($unit(1, 'SOR_010') !== null && GetLeader(1)[0]->Deployed && GetSWUVar('SWUPGN_VIEWER') === '1', 'the converted board survives WriteGamestate → ParseGamestate');
$wing2 = $unit(1, 'JTL_095');
SwuPgnTestCheck($wing2 !== null && in_array('JTL_058', $subIds($wing2), true), 'subcards survive the round trip');
array_map('unlink', glob("./SWUSim/Games/$gameName/*")); @rmdir("./SWUSim/Games/$gameName");

SwuPgnTestFinish();
