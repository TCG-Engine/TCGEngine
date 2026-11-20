<?php
// SWU-PGN writer, named Action abilities: an ABILITY note may carry the ability's id, printed text and use limit, and
// name its HOST by uid (a granted ability belongs to the unit using it, grantedBy the card that gave it — §10.1, W-31,
// W-49). The matcher copies them onto ABILITY_ACTIVATE; a note without them writes exactly today's record.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 DevTools/tdd-regression/test_swupgn_writer_named_ability.php
require_once __DIR__ . '/fixtures/swupgn_test_helpers.php';
require_once __DIR__ . '/../../SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
require_once __DIR__ . '/../../SWUSim/Custom/SwuPgnWriter/_include.php';
require_once __DIR__ . '/../../AppCore/SWU/SwuPgn/SwuPgnValidate.php';

function NCtx(): array {
    $L = SwuPgnLedgerInit([1 => ['HMW_009', 'ASH_230', 'SOR_095'], 2 => ['SOR_096']]);
    SwuPgnLedgerPut($L, 1, 'base', SwuPgnLedgerNewToken($L, 'SOR_005'));
    return ['ledger' => $L, 'counters' => [], 'round' => 1, 'phase' => 'A'];
}
function NPlace(array &$ctx, int $seat, string $zone, string $cardID, ?int $uid = null): void {
    SwuPgnLedgerPut($ctx['ledger'], $seat, $zone, SwuPgnLedgerTake($ctx['ledger'], $seat, 'deck', $cardID), $uid);
}
// The record's fields fit ABILITY_ACTIVATE's shape, and `limit` never travels without `ability` [W-49].
function NShapeOk(array $e): bool {
    $allowed = array_keys(_SwuPgnEventShapes()['ABILITY_ACTIVATE']);
    $extra = array_diff(array_keys($e), array_merge($allowed, ['t', 'seq', 'for']));
    return $extra === [] && (!isset($e['limit']) || is_string($e['ability'] ?? null));
}

// ── A granted, limited Action: Improvised Identity used on Chewbacca ─────────────────────────────────
$ctx = NCtx(); NPlace($ctx, 1, 'ground', 'HMW_009', 7); NPlace($ctx, 1, 'ground', 'ASH_230', 8);
$r = SwuPgnMatch([], [['t' => 'ABILITY', 'p' => 1, 'cardID' => 'ASH_230', 'uid' => 7, 'kind' => 'action', 'grantedBy' => 'ASH_230',
    'ability' => 'ASH_230.0@ASH#230', 'title' => 'Action: Search the top 3 cards of your deck for a ground unit and discard it.',
    'limit' => ['max' => 1, 'per' => 'round']]], $ctx);
$e = $r['events'][0] ?? [];
SwuPgnTestEq($e['t'] ?? null, 'ABILITY_ACTIVATE', 'the note writes an ABILITY_ACTIVATE');
SwuPgnTestEq($e['card'] ?? null, 'HMW#009', 'card = the HOST (by uid), not the granting upgrade');
SwuPgnTestEq($e['grantedBy'] ?? null, 'ASH#230', 'grantedBy = the granting upgrade [W-31]');
SwuPgnTestEq($e['ability'] ?? null, 'ASH_230.0@ASH#230', 'ability copied verbatim');
SwuPgnTestEq($e['title'] ?? null, 'Action: Search the top 3 cards of your deck for a ground unit and discard it.', 'title copied verbatim');
SwuPgnTestEq($e['limit'] ?? null, ['max' => 1, 'per' => 'round'], 'limit copied verbatim [W-49]');
SwuPgnTestCheck(NShapeOk($e), 'the record fits the ABILITY_ACTIVATE shape', $e);

// ── A note without the new fields: today's record, byte for byte ─────────────────────────────────────
$ctx = NCtx();
$r = SwuPgnMatch([['k' => 'exhaust', 'seat' => 1, 'target' => 'leader@1', 'cardID' => 'SOR_005']],
    [['t' => 'ABILITY', 'p' => 1, 'cardID' => 'SOR_005', 'kind' => 'action']], $ctx);
SwuPgnTestEq(array_map(fn($x) => array_diff_key($x, ['seq' => 1]), $r['events']),
    [['t' => 'ABILITY_ACTIVATE', 'p' => 1, 'card' => 'SOR#005', 'kind' => 'action'], ['t' => 'EXHAUST', 'card' => 'SOR#005']],
    'no new fields on the note → no new fields on the record');

// ── End to end: the engine's own note, through the writer (writer revision 4) ────────────────────────
chdir('/var/www/html/TCGEngine');
if (!function_exists('ConvertMzIDToAbsolute'))      { function ConvertMzIDToAbsolute($m,$p):string{return '';} }
if (!function_exists('QueueDamageAnimation'))       { function QueueDamageAnimation($t,$a):void{} }
if (!function_exists('QueueRestoreAnimation'))      { function QueueRestoreAnimation($t,$a):void{} }
if (!function_exists('QueuePreventedDamageAnimation')) { function QueuePreventedDamageAnimation($t):void{} }
if (!function_exists('QueueShieldBreakAnimation'))  { function QueueShieldBreakAnimation($t):void{} }
foreach (['DeterministicRNG', 'CoreZoneModifiers', 'GameAuth'] as $f) include_once "./Core/$f.php";
include_once './SWUSim/ZoneClasses.php'; include_once './SWUSim/ZoneAccessors.php'; include_once './SWUSim/GamestateParser.php';
foreach (['Assertions', 'Cards', 'CommonSetup', 'GameStateBuilder', 'GameTestAdapter', 'SchemaTestRunner', 'TestRunner'] as $f) include_once "./SWUSim/Tests/Framework/$f.php";
global $gameName, $playerID;
$GLOBALS['SWUPGN_TEST_USERNAMES'] = [];
$games = [];
function EGame(callable $build, array $my = []): GameTestAdapter {
    global $gameName, $games;
    $gameName = 'swupgnnamed_' . count($games) . '_' . getmypid();
    $games[] = $gameName;
    @mkdir('./SWUSim/Games/' . $gameName, 0777, true);
    $b = new GameStateBuilder();
    CommonSetup($b, 'grw', 'brk', $my + ['resourceCount' => 8], ['resourceCount' => 8]);
    $b->WithActivePlayer(1);
    foreach (['SOR_157', 'SOR_063', 'SOR_063', 'SOR_063'] as $c) { $b->WithCardInDeckForPlayer(1, $c); $b->WithCardInDeckForPlayer(2, $c); }
    $build($b);
    $g = new GameTestAdapter(); $g->loadState($b);
    SwuPgnRecForceForTest($gameName);
    return $g;
}
function EFrame(callable $fn): array {
    global $gameName;
    $n = count(SwuPgnEventsRead($gameName));
    SwuPgnRecFrameBegin();
    ob_start();
    try { $fn(); } finally { ob_end_clean(); }
    SwuPgnRecFrameCommit();
    return array_slice(SwuPgnEventsRead($gameName), $n);
}
function EFind(array $events, string $t): ?array { foreach ($events as $e) if ($e['t'] === $t) return $e; return null; }
function ECopy(int $uid): ?string { global $gameName; return SwuPgnLedgerByUid(SwuPgnStateRead($gameName)['ledger'], $uid); }
function EUid(int $seat, string $cardID): int {
    foreach (array_merge(GetGroundArena($seat), GetSpaceArena($seat)) as $u) if (empty($u->removed) && $u->CardID === $cardID) return intval($u->UniqueID);
    return 0;
}

// Improvised Identity used on Chewbacca: card = Chewbacca, grantedBy = the upgrade, a per-copy ability id, the limit.
$g = EGame(fn($b) => $b->WithGroundUnitForPlayer(1, 'HMW_009')->WithUpgradesOnGroundUnitForPlayer(1, 0, [GameStateBuilder::Upgrade('ASH_230', 1)]));
$ab = EFind(EFrame(fn() => SWUUnitAction(1, 'myGroundArena-0', 'ASH_230.0')), 'ABILITY_ACTIVATE');
SwuPgnTestEq($ab['card'] ?? null, ECopy(EUid(1, 'HMW_009')), 'engine: card = the host Chewbacca');
SwuPgnTestEq($ab['grantedBy'] ?? null, SwuPgnCardRef('ASH_230'), 'engine: grantedBy = Improvised Identity');
SwuPgnTestCheck(str_starts_with((string)($ab['ability'] ?? ''), 'ASH_230.0@'), 'engine: ability = the id + the copy used (per-copy limit)', $ab['ability'] ?? null);
SwuPgnTestEq($ab['limit'] ?? null, ['max' => 1, 'per' => 'round'], 'engine: limit {1, round}');
SwuPgnTestCheck(str_starts_with((string)($ab['title'] ?? ''), 'Action: Search the top 3'), 'engine: title = the printed Action', $ab['title'] ?? null);
SwuPgnTestCheck(NShapeOk($ab ?? []), 'engine: the record fits the ABILITY_ACTIVATE shape', $ab);
// Poe as a pilot: his Action is printed on Poe himself — card = Poe, no grantedBy.
$g = EGame(fn($b) => $b->WithSpaceUnitForPlayer(1, 'SOR_225')->WithSpaceUnitForPlayer(1, 'SOR_225')->WithUpgradesOnSpaceUnitForPlayer(1, 0, [GameStateBuilder::Upgrade('JTL_013', 1)]), ['leaderCardID' => 'JTL_013']);  // his use lives on the leader
$ab = EFind(EFrame(fn() => SWUUnitAction(1, 'mySpaceArena-0', 'JTL_013.u0')), 'ABILITY_ACTIVATE');
SwuPgnTestEq([$ab['card'] ?? null, array_key_exists('grantedBy', $ab ?? [])], [SwuPgnCardRef('JTL_013'), false], 'engine: a pilot\'s own Action is Poe\'s, no grantedBy');
SwuPgnTestEq($ab['ability'] ?? null, 'JTL_013.u0', 'engine: Poe\'s ability id');

// A leader's front Action: ABILITY_ACTIVATE carries its id and printed text.
$g = EGame(fn($b) => $b->WithCardInHandForPlayer(1, 'SOR_046'), ['leaderCardID' => 'JTL_014']);
$ab = EFind(EFrame(fn() => SWULeaderAction(1, 'JTL_014', 0, 'JTL_014.0')), 'ABILITY_ACTIVATE');
SwuPgnTestEq($ab['ability'] ?? null, 'JTL_014.0', 'engine: a leader Action\'s ability id');
SwuPgnTestCheck(str_starts_with((string)($ab['title'] ?? ''), 'Action [Exhaust]: Discard a card'), 'engine: the leader Action\'s printed text', $ab['title'] ?? null);

// ── abilityUses + ABILITY_RESET (W-49): the writer keeps the fold's use table, resets it on a zone change and at the
//    phase/round boundaries, and carries it in every keyframe.
global $gameName;
$g = EGame(fn($b) => $b->WithSpaceUnitForPlayer(1, 'TWI_082'));
$mag = ECopy(EUid(1, 'TWI_082'));
EFrame(fn() => SWUUnitAction(1, 'mySpaceArena-0', 'TWI_082.0'));
$uses = SwuPgnStateRead($gameName)['abilityUses'][1] ?? null;
SwuPgnTestEq($uses, [['card' => $mag, 'ability' => 'TWI_082.0', 'count' => 1, 'per' => 'round', 'max' => 1]], 'a limited use is in the writer\'s abilityUses table');
$kf = SwuPgnKeyframe(SwuPgnSnapshot(), SwuPgnStateRead($gameName)['ledger'], ['round' => 1, 'phase' => 'action', 'initiativeTaken' => false, 'abilityUses' => SwuPgnStateRead($gameName)['abilityUses']]);
SwuPgnTestEq([$kf['players'][1]['abilityUses'] ?? null, $kf['players'][2]['abilityUses'] ?? null], [$uses, []], 'a keyframe carries each seat\'s abilityUses (an empty list when none)');
$ev = EFrame(fn() => SWUDefeatUnit(1, 'mySpaceArena-0'));
$reset = EFind($ev, 'ABILITY_RESET');
SwuPgnTestEq($reset['card'] ?? null, $mag, 'the card leaving play writes ABILITY_RESET for it');
SwuPgnTestEq(SwuPgnStateRead($gameName)['abilityUses'][1] ?? [], [], '…and its entries leave the table');
// A once-each-PHASE use (Heavy Ion Cannon) is cleared at the regroup's PHASE_START.
$g = EGame(fn($b) => $b->WithUpgradeOnBaseForPlayer(1, 'HMW_172')->WithCardInHandForPlayer(1, 'SOR_101')->WithGroundUnitForPlayer(2, 'SOR_049'));
EFrame(fn() => SWUBaseAction(1, 'HMW_172.0'));
SwuPgnTestEq(count(SwuPgnStateRead($gameName)['abilityUses'][1] ?? []), 1, 'a once-each-phase use is in the table');
EFrame(fn() => SwuPgnRecBoundary('RGS'));   // the engine calls it mid-frame, at the top of RegroupPhaseStart
SwuPgnTestEq(SwuPgnStateRead($gameName)['abilityUses'][1] ?? [], [], 'the regroup PHASE_START clears phase uses');

foreach ($games as $x) { _SwuPgnRmTree(SwuPgnRecDir($x)); exec('rm -rf ' . escapeshellarg('./SWUSim/Games/' . $x)); }

SwuPgnTestFinish();
