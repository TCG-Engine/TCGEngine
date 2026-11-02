<?php
// SWU-PGN writer, Homeworlds revision of 1.0 (2026-10-26): what the writer now owes a reader.
//   - a DEFEAT on base@N for every defeated base, before its GAME_END — by damage (attack / nonCombatDamage /
//     frameworkEffect) or outright (Grand Moff Tarkin: no DAMAGE, the HP stands);
//   - DAMAGE.src / HEAL.src / DEFEAT.defeatedBy only ever a card or base@N, else left out;
//   - an attachment rebound inside its arena is ONE MOVE whose from = to (no trip through outsideTheGame);
//   - an action ability's cost is paid after it is announced, and §9.1's runs (ability precursors, arrivals);
//   - damage kills are nonCombatDamage, an HP-loss kill is the framework's even with a card resolving;
//   - a base Action granted by a Fortify upgrade is the base's ability, grantedBy the upgrade — and its damage is the base's;
//   - [WriterRevision] and %%% CARDS backSideName.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 DevTools/tdd-regression/test_swupgn_writer_homeworlds.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
chdir('/var/www/html/TCGEngine');
if (!function_exists('ConvertMzIDToAbsolute'))      { function ConvertMzIDToAbsolute($m,$p):string{return '';} }
if (!function_exists('QueueDamageAnimation'))       { function QueueDamageAnimation($t,$a):void{} }
if (!function_exists('QueueRestoreAnimation'))      { function QueueRestoreAnimation($t,$a):void{} }
if (!function_exists('QueuePreventedDamageAnimation')) { function QueuePreventedDamageAnimation($t):void{} }
if (!function_exists('QueueShieldBreakAnimation'))  { function QueueShieldBreakAnimation($t):void{} }
foreach (['DeterministicRNG', 'CoreZoneModifiers', 'GameAuth'] as $f) include_once "./Core/$f.php";
include_once './SWUSim/ZoneClasses.php'; include_once './SWUSim/ZoneAccessors.php';
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php'; include_once './SWUSim/GamestateParser.php';
foreach (['Assertions', 'Cards', 'CommonSetup', 'GameStateBuilder', 'GameTestAdapter', 'SchemaTestRunner', 'TestRunner'] as $f) include_once "./SWUSim/Tests/Framework/$f.php";
require_once __DIR__ . '/fixtures/swupgn_test_helpers.php';
global $gameName, $playerID;
$GLOBALS['SWUPGN_TEST_USERNAMES'] = [];

// ── pure matcher ────────────────────────────────────────────────────────────────────────────────
function HCtx(): array {
    $L = SwuPgnLedgerInit([1 => ['SOR_095', 'SOR_172', 'SOR_046'], 2 => ['SOR_096', 'SOR_049']]);
    SwuPgnLedgerPut($L, 1, 'base', SwuPgnLedgerNewToken($L, 'HMW_004'));
    return ['ledger' => $L, 'counters' => [], 'round' => 3, 'phase' => 'A'];
}
function HPlace(array &$ctx, int $seat, string $zone, string $cardID, ?int $uid = null): string {
    $copy = SwuPgnLedgerTake($ctx['ledger'], $seat, 'deck', $cardID);
    SwuPgnLedgerPut($ctx['ledger'], $seat, $zone, $copy, $uid);
    return $copy;
}
$shape = fn(array $r) => array_map(fn($e) => array_intersect_key($e, array_flip(['t', 'card', 'tgt', 'src', 'reason', 'defeatedBy', 'from', 'to', 'kind', 'attachedTo'])), $r['events']);
$baseHit = fn(int $amt, int $hp) => ['k' => 'damage', 'seat' => 2, 'target' => 'base@2', 'amount' => $amt, 'hp' => $hp];

// Sources: only a card or base@N, else none.
$ctx = HCtx(); $wampa = HPlace($ctx, 1, 'ground', 'SOR_095', 7);
$r = SwuPgnMatch([['k' => 'damage', 'seat' => 1, 'target' => 7, 'amount' => 1, 'hp' => 2]], [['t' => 'DAMAGE', 'src' => 'unknown', 'tgt' => 7, 'amt' => 1, 'combat' => false]], $ctx);
SwuPgnTestEq($shape($r), [['t' => 'DAMAGE', 'tgt' => $wampa]], 'a DAMAGE whose source is unknown carries no src');
$ctx = HCtx();
$r = SwuPgnMatch([['k' => 'damage', 'seat' => 1, 'target' => 'base@1', 'amount' => 2, 'hp' => 28]], [['t' => 'DAMAGE', 'src' => 'rule:deck-out', 'tgt' => 'base@1', 'amt' => 2, 'combat' => false]], $ctx);
SwuPgnTestEq($shape($r), [['t' => 'DAMAGE', 'tgt' => 'base@1']], 'deck-out damage (the rules dealt it) carries no src');
$ctx = HCtx(); HPlace($ctx, 1, 'ground', 'SOR_095', 7);
$r = SwuPgnMatch([['k' => 'damage', 'seat' => 1, 'target' => 7, 'amount' => 1, 'hp' => 2], ['k' => 'heal', 'seat' => 1, 'target' => 'base@1', 'amount' => 1, 'hp' => 30]], [], $ctx);
SwuPgnTestEq(array_map(fn($e) => [$e['t'], array_key_exists('src', $e)], $r['events']), [['DAMAGE', false], ['HEAL', false]], 'unannounced DAMAGE / HEAL carry no src');

// A defeated base: a DEFEAT on base@N right after the lethal record.
$ctx = HCtx(); $wampa = HPlace($ctx, 1, 'ground', 'SOR_095', 7);
$r = SwuPgnMatch([$baseHit(4, 0)], [['t' => 'DAMAGE', 'src' => 7, 'tgt' => 'base@2', 'amt' => 4, 'combat' => true]], $ctx);
SwuPgnTestEq($shape($r), [['t' => 'DAMAGE', 'tgt' => 'base@2', 'src' => $wampa], ['t' => 'DEFEAT', 'card' => 'base@2', 'reason' => 'attack', 'defeatedBy' => $wampa]],
    'combat damage takes the base to 0: DEFEAT base@2, reason attack, defeatedBy the attacker');
$ctx = HCtx(); $fire = HPlace($ctx, 1, 'discard', 'SOR_172');
$r = SwuPgnMatch([$baseHit(4, -1)], [['t' => 'DAMAGE', 'src' => 'SOR_172', 'tgt' => 'base@2', 'amt' => 4, 'combat' => false]], $ctx);
SwuPgnTestEq(array_slice($shape($r), 1), [['t' => 'DEFEAT', 'card' => 'base@2', 'reason' => 'nonCombatDamage', 'defeatedBy' => $fire]],
    'an ability\'s damage takes it to 0 (or below): reason nonCombatDamage, defeatedBy the card that dealt it');
$ctx = HCtx();
$r = SwuPgnMatch([$baseHit(1, 0)], [['t' => 'DAMAGE', 'src' => 'rule:deck-out', 'tgt' => 'base@2', 'amt' => 1, 'combat' => false]], $ctx);
SwuPgnTestEq(array_slice($shape($r), 1), [['t' => 'DEFEAT', 'card' => 'base@2', 'reason' => 'frameworkEffect']], 'damage no card dealt (drawing from an empty deck): frameworkEffect, no defeatedBy');
$ctx = HCtx(); HPlace($ctx, 1, 'ground', 'SOR_095', 7);
$r = SwuPgnMatch([$baseHit(6, -2)], [['t' => 'DAMAGE', 'src' => 7, 'tgt' => 'base@2', 'amt' => 3, 'combat' => true], ['t' => 'DAMAGE', 'src' => 7, 'tgt' => 'base@2', 'amt' => 3, 'combat' => true]], $ctx);
SwuPgnTestEq(array_map(fn($e) => [$e['t'], $e['hp'] ?? null], $r['events']), [['DAMAGE', 1], ['DAMAGE', -2], ['DEFEAT', null]], 'the DEFEAT follows the record that took the base to 0, not an earlier hit');
$r = SwuPgnMatch([$baseHit(6, -2)], [['t' => 'DAMAGE', 'src' => 7, 'tgt' => 'base@2', 'amt' => 4, 'combat' => true], ['t' => 'DAMAGE', 'src' => 7, 'tgt' => 'base@2', 'amt' => 2, 'combat' => true]], $ctx);
SwuPgnTestEq(array_column($r['events'], 't'), ['DAMAGE', 'DEFEAT', 'DAMAGE'], 'one DEFEAT per base — a hit after it fell writes no second one');
$ctx = HCtx();
$r = SwuPgnMatch([$baseHit(3, 27)], [['t' => 'DAMAGE', 'src' => 'rule:deck-out', 'tgt' => 'base@2', 'amt' => 3, 'combat' => false]], $ctx);
SwuPgnTestEq(array_column($r['events'], 't'), ['DAMAGE'], 'a base left standing is not defeated');
// Outright (Grand Moff Tarkin): no damage is dealt — the engine's "fill the damage in" is not a DAMAGE.
$ctx = HCtx();
$r = SwuPgnMatch([$baseHit(21, 0)], [['t' => 'BASE_DEFEAT', 'seat' => 2, 'by' => 'HMW_004']], $ctx);
SwuPgnTestEq($shape($r), [['t' => 'DEFEAT', 'card' => 'base@2', 'reason' => 'ability', 'defeatedBy' => 'HMW#004']],
    'a base defeated outright: DEFEAT base@2 reason ability, defeatedBy Tarkin — and no DAMAGE, so its HP stands');
SwuPgnTestEq($r['gaps'], [], '…the damage the engine filled in is claimed, not a gap');

// Rebinds inside an arena: ONE MOVE, from = to.
$pilotCtx = function (string $arena, bool $attached) {
    $ctx = HCtx(); HPlace($ctx, 1, $arena, 'SOR_046', 7);
    SwuPgnLedgerPut($ctx['ledger'], 1, $attached ? 'attached:7' : 'ground', SwuPgnLedgerNewToken($ctx['ledger'], 'JTL_046'), $attached ? null : 9);
    return $ctx;
};
$mshape = fn(array $r) => array_map(fn($e) => [$e['t'], $e['from'] ?? null, $e['to'] ?? null, $e['kind'] ?? null, $e['attachedTo'] ?? null], $r['events']);
$ctx = $pilotCtx('ground', true);
$r = SwuPgnMatch([['k' => 'move', 'seat' => 1, 'cardID' => 'JTL_046', 'from' => 'ground', 'to' => 'ground', 'uid' => 9, 'host' => 7, 'kind' => 'upgrade', 'detach' => true, 'exhausted' => true]], [], $ctx);
SwuPgnTestEq($mshape($r), [['MOVE', 'ground', 'ground', 'unit', null], ['EXHAUST', null, null, null, null]], 'a pilot ejected in its arena: one MOVE ground → ground as a unit · EXHAUST');
SwuPgnTestEq(SwuPgnLedgerByUid($ctx['ledger'], 9), SwuPgnCardRef('JTL_046'), '…the ejected pilot is its new unit');
$ctx = $pilotCtx('space', true);
$r = SwuPgnMatch([['k' => 'move', 'seat' => 1, 'cardID' => 'JTL_046', 'from' => 'space', 'to' => 'ground', 'uid' => 9, 'host' => 7, 'kind' => 'upgrade', 'detach' => true, 'exhausted' => false]], [], $ctx);
SwuPgnTestEq($mshape($r), [['MOVE', 'space', 'ground', 'unit', null]], 'ejected from a space Vehicle to the ground: one MOVE (the fold detaches on entering an arena)');
$ctx = $pilotCtx('ground', false);
$r = SwuPgnMatch([['k' => 'move', 'seat' => 1, 'cardID' => 'JTL_046', 'from' => 'ground', 'to' => 'ground', 'uid' => 9, 'host' => 7, 'kind' => 'upgrade', 'attachFromPlay' => true]], [], $ctx);
SwuPgnTestEq($mshape($r), [['MOVE', 'ground', 'ground', 'upgrade', SwuPgnCardRef('SOR_046')]], 'a unit made a pilot: one MOVE onto its host');
$ctx = HCtx(); HPlace($ctx, 1, 'ground', 'SOR_046', 7); HPlace($ctx, 1, 'ground', 'SOR_095', 8);
SwuPgnLedgerPut($ctx['ledger'], 1, 'attached:7', SwuPgnLedgerNewToken($ctx['ledger'], 'SOR_120'));
$r = SwuPgnMatch([['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_120', 'from' => 'ground', 'to' => 'ground', 'host' => 8, 'kind' => 'upgrade', 'fromHost' => 7]], [], $ctx);
SwuPgnTestEq($mshape($r), [['MOVE', 'ground', 'ground', 'upgrade', SwuPgnCardRef('SOR_095')]], 'an upgrade moved to another host: one MOVE onto the new host');

// An action ability: announced, then its cost (the card exhausting) under its own step.
$ctx = HCtx();
$r = SwuPgnMatch([['k' => 'exhaust', 'seat' => 1, 'target' => 'leader@1', 'cardID' => 'HMW_004']], [['t' => 'ABILITY', 'p' => 1, 'cardID' => 'HMW_004', 'kind' => 'action']], $ctx);
SwuPgnTestEq(array_map(fn($e) => [$e['seq'], $e['t'], $e['for'] ?? null], $r['events']), [['R3.A.1', 'ABILITY_ACTIVATE', null], ['R3.A.1a', 'EXHAUST', null]],
    'an action ability: ABILITY_ACTIVATE first, the exhaust it pays with after it');
// §9.1 runs, on a sequenced list.
$seqOf = function (array $out, array $notes) {
    $ctx = HCtx();
    $run = ['ctx' => &$ctx, 'out' => array_map(fn($x) => ['ev' => $x[0], 'top' => $x[1], 'owner' => $x[2]], $out), 'notes' => $notes];
    return array_map(fn($e) => [$e['t'], $e['for'] ?? null], _SwuPgnMSequence($run));
};
SwuPgnTestEq($seqOf([
    [['t' => 'CHOICE', 'p' => 1, 'offered' => ['SOR#095'], 'chose' => 0], false, 0],
    [['t' => 'EXHAUST', 'card' => 'HMW#004'], false, 0],
    [['t' => 'ABILITY_ACTIVATE', 'p' => 1, 'card' => 'HMW#004', 'kind' => 'action'], true, 0],
], [['t' => 'ABILITY', 'kind' => 'action']]), [['CHOICE', null], ['EXHAUST', null], ['ABILITY_ACTIVATE', null]],
    '§9.1: only CHOICE / MODAL_CHOICE may precede an action ability — an EXHAUST naming it does not link the run');
SwuPgnTestEq($seqOf([
    [['t' => 'MOVE', 'card' => 'TOKEN:beast#6445363199', 'from' => 'outsideTheGame', 'to' => 'ground', 'p' => 1, 'kind' => 'unit'], false, null],
    [['t' => 'STATS', 'card' => 'TOKEN:beast#6445363199', 'power' => 3, 'hp' => 3, 'keywords' => []], false, null],
    [['t' => 'EXHAUST', 'card' => 'TOKEN:beast#6445363199'], false, null],
    [['t' => 'EXHAUST_RESOURCES', 'p' => 1, 'amount' => 2], false, 0],
    [['t' => 'MOVE', 'card' => 'SOR#095', 'from' => 'hand', 'to' => 'ground', 'p' => 1, 'kind' => 'unit'], false, 0],
    [['t' => 'PLAY', 'p' => 1, 'card' => 'SOR#095', 'zone' => 'ground', 'cost' => 2], true, 0],
], [['t' => 'PLAY']]), [['MOVE', null], ['STATS', null], ['EXHAUST', null], ['EXHAUST_RESOURCES', 'R3.A.1'], ['MOVE', 'R3.A.1'], ['PLAY', null]],
    '§9.1: another card arriving in play ends the run — the Beast\'s arrival, STATS and EXHAUST are not the play\'s');

// ── the engine ──────────────────────────────────────────────────────────────────────────────────
$games = [];
function HGame(callable $build, array $my = [], array $their = []): GameTestAdapter {
    global $gameName, $games;
    $gameName = 'swupgnhmw_' . count($games) . '_' . getmypid();
    $games[] = $gameName;
    @mkdir('./SWUSim/Games/' . $gameName, 0777, true);
    $b = new GameStateBuilder();
    CommonSetup($b, 'grw', 'brk', $my + ['resourceCount' => 8], $their + ['resourceCount' => 8]);
    $b->WithActivePlayer(1);
    foreach (['SOR_101', 'SOR_102', 'SOR_103', 'SOR_104'] as $c) { $b->WithCardInDeckForPlayer(1, $c); $b->WithCardInDeckForPlayer(2, $c); }
    $build($b);
    $g = new GameTestAdapter(); $g->loadState($b);
    SwuPgnRecForceForTest($gameName);
    return $g;
}
function HFrame(callable $fn): array {
    global $gameName;
    $n = count(SwuPgnEventsRead($gameName));
    SwuPgnRecFrameBegin();
    ob_start();
    try { $fn(); } finally { ob_end_clean(); }
    SwuPgnRecFrameCommit();
    return array_slice(SwuPgnEventsRead($gameName), $n);
}
function HFind(array $events, string $t, array $where = []): ?array {
    foreach ($events as $e) {
        if ($e['t'] !== $t) continue;
        foreach ($where as $k => $v) if (($e[$k] ?? null) !== $v) continue 2;
        return $e;
    }
    return null;
}
function HTypes(array $events): string { return implode(' ', array_map(fn($e) => $e['seq'] . ':' . $e['t'], $events)); }
function HCopy(int $uid): ?string { global $gameName; return SwuPgnLedgerByUid(SwuPgnStateRead($gameName)['ledger'], $uid); }
function HUid(int $seat, string $cardID): int {
    foreach (array_merge(GetGroundArena($seat), GetSpaceArena($seat)) as $u) if (empty($u->removed) && $u->CardID === $cardID) return intval($u->UniqueID);
    return 0;
}
function HOutDoc(): ?array {
    global $gameName;
    $path = SwuPgnRecDir($gameName) . '/out/all.swupgn';
    return is_file($path) ? SwuPgnParse(file_get_contents($path)) : null;
}

// Grand Moff Tarkin defeats a base outright: the file says so with a DEFEAT, and the HP stands.
$g = HGame(fn($b) => null, ['leaderCardID' => 'HMW_004'], ['baseDamage' => 21]);
$ev = HFrame(function () { $GLOBALS['playerID'] = 1; SWULogSetSource(1, 'HMW_004'); SWUDefeatBase(2, 1); });
$def = HFind($ev, 'DEFEAT', ['card' => 'base@2']);
SwuPgnTestCheck($def !== null && $def['reason'] === 'ability' && SwuPgnBaseId(strval($def['defeatedBy'] ?? '')) === 'HMW#004', 'Tarkin: DEFEAT base@2, reason ability, defeatedBy Tarkin', HTypes($ev));
SwuPgnTestCheck(HFind($ev, 'DAMAGE', ['tgt' => 'base@2']) === null, 'Tarkin: no DAMAGE — no damage was dealt', HTypes($ev));
$types = array_column($ev, 't');
SwuPgnTestCheck(($i = array_search('DEFEAT', $types, true)) !== false && array_search('GAME_END', $types, true) > $i, 'Tarkin: the DEFEAT comes before the GAME_END it causes', HTypes($ev));
$doc = HOutDoc();
SwuPgnTestCheck($doc !== null, 'Tarkin: the finished file was written (it checks clean)');
if ($doc !== null) {
    $fold = SwuPgnFold($doc['events']);
    SwuPgnTestEq([$fold['players'][2]['baseDefeated'] ?? null, $fold['players'][2]['baseHp'] > 0], [true, true], 'Tarkin: the folded base is defeated with HP left');
    SwuPgnTestEq(array_slice(array_keys($doc['headers']), 0, 2), ['Game', 'WriterRevision'], '[WriterRevision] right after [Game]');
    SwuPgnTestCheck(preg_match('/^\d+$/', $doc['headers']['WriterRevision'] ?? '') === 1 && intval($doc['headers']['WriterRevision']) >= 1, '[WriterRevision] is a revision number', $doc['headers']['WriterRevision'] ?? null);
    $entry = null;
    foreach ($doc['cards'] as $c) if (($c['id'] ?? null) === 'HMW#004') $entry = $c;
    SwuPgnTestEq($entry, ['id' => 'HMW#004', 'name' => 'Grand Moff Tarkin, Tyrant of the Outer Rim', 'backSideName' => 'The Death Star, Icon of Tyranny'],
        '%%% CARDS names Tarkin by his printed front, the deployed side as backSideName');
    SwuPgnTestEq(SwuPgnValidate($doc)['errors'], [], 'Tarkin: the file validates');
}

// A lethal attack on a base: DEFEAT base@2, reason attack, defeatedBy the attacker, before the GAME_END.
$g = HGame(fn($b) => $b->WithGroundUnitForPlayer(1, 'SOR_095'), [], ['baseDamage' => 28]);
$atk = HCopy(HUid(1, 'SOR_095'));
$ev = HFrame(fn() => $g->declareAttack(1, 'myGroundArena-0', 'theirBase-0'));
SwuPgnTestCheck(HFind($ev, 'DEFEAT', ['card' => 'base@2', 'reason' => 'attack', 'defeatedBy' => $atk]) !== null, 'a lethal attack: DEFEAT base@2, reason attack, defeatedBy the attacker', HTypes($ev));
$types = array_column($ev, 't');
SwuPgnTestCheck(($i = array_search('DEFEAT', $types, true)) !== false && array_search('GAME_END', $types, true) > $i, '…before the GAME_END', HTypes($ev));

// A unit killed by an ability's damage: reason nonCombatDamage, defeatedBy the card that dealt it.
$g = HGame(fn($b) => $b->WithCardInHandForPlayer(1, 'SOR_172')->WithGroundUnitForPlayer(2, 'SOR_095')->WithGroundUnitForPlayer(2, 'SOR_049'));
$tgt = HCopy(HUid(2, 'SOR_095'));
$ev = array_merge(HFrame(fn() => $g->playCardFromHand(1, 0)), HFrame(fn() => $g->answerDecision(1, 'theirGroundArena-0')));
$event = HFind($ev, 'PLAY_EVENT', ['p' => 1]);
SwuPgnTestCheck($event !== null && HFind($ev, 'DEFEAT', ['card' => $tgt, 'reason' => 'nonCombatDamage', 'defeatedBy' => $event['card']]) !== null,
    'a damage kill by an event: reason nonCombatDamage, defeatedBy the event card', HTypes($ev));
// A unit whose HP fell to its damage with no damage dealt is the framework's — even while a card is resolving.
$g = HGame(fn($b) => $b->WithGroundUnitForPlayer(1, 'SOR_095'));
$ev = HFrame(function () { SWULogSetSource(2, 'SOR_172'); $u = &GetGroundArena(1); $u[0]->Damage = 3; unset($u); SWUCheckShrinkDefeats(); });
$d = HFind($ev, 'DEFEAT');
SwuPgnTestEq([$d['reason'] ?? null, $d['defeatedBy'] ?? null], ['frameworkEffect', null], 'an HP-loss kill: reason frameworkEffect, no defeatedBy, whatever card is resolving');

// A base Action granted by a Fortify upgrade (Heavy Ion Cannon) is the base's, grantedBy the upgrade; its damage is the base's.
$g = HGame(fn($b) => $b->WithUpgradeOnBaseForPlayer(1, 'HMW_172')->WithCardInHandForPlayer(1, 'SOR_101')->WithGroundUnitForPlayer(2, 'SOR_049'));
$cannon = null;
foreach (SwuPgnStateRead($gameName)['ledger']['zones'][1]['base'] ?? [] as $c) if (SwuPgnBaseId($c) === SwuPgnCardRef('HMW_172')) $cannon = $c;
$ev = HFrame(fn() => SWUBaseAction(1));
$ab = HFind($ev, 'ABILITY_ACTIVATE');
SwuPgnTestEq($ab === null ? null : array_intersect_key($ab, array_flip(['p', 'card', 'kind', 'grantedBy'])), ['p' => 1, 'card' => 'base@1', 'kind' => 'action', 'grantedBy' => $cannon],
    'Heavy Ion Cannon\'s Action: ABILITY_ACTIVATE on base@1, kind action, grantedBy the upgrade', HTypes($ev));
SwuPgnTestCheck($ab !== null && preg_match('/^R\d+\.A\.\d+$/', $ab['seq']) === 1, '…a numbered action of its own', $ab['seq'] ?? null);
$ev = array_merge(HFrame(fn() => $g->answerDecision(1, 'myHand-0')), HFrame(fn() => $g->answerDecision(1, 'theirGroundArena-0')));
SwuPgnTestCheck(HFind($ev, 'DAMAGE', ['src' => 'base@1', 'amt' => 2]) !== null, 'its 2 damage: src base@1 — the base dealt it', HTypes($ev));
// An upgrade's own Action (Bacta Tank: "Action [defeat this upgrade]") is the upgrade's, and so is its defeat.
$g = HGame(fn($b) => $b->WithUpgradeOnBaseForPlayer(1, 'HMW_037'));
$tank = null;
foreach (SwuPgnStateRead($gameName)['ledger']['zones'][1]['base'] ?? [] as $c) if (SwuPgnBaseId($c) === SwuPgnCardRef('HMW_037')) $tank = $c;
$ev = HFrame(fn() => SWUBaseAction(1));
$ab = HFind($ev, 'ABILITY_ACTIVATE');
SwuPgnTestEq($ab === null ? null : array_intersect_key($ab, array_flip(['card', 'kind', 'grantedBy'])), ['card' => $tank, 'kind' => 'action'], 'Bacta Tank\'s own Action: the upgrade\'s ability, no grantedBy', HTypes($ev));
SwuPgnTestCheck(HFind($ev, 'DEFEAT', ['card' => $tank, 'reason' => 'ability', 'defeatedBy' => $tank]) !== null, '…its cost: DEFEAT, reason ability, defeatedBy itself', HTypes($ev));

foreach ($games as $x) { _SwuPgnRmTree(SwuPgnRecDir($x)); exec('rm -rf ' . escapeshellarg('./SWUSim/Games/' . $x)); }
SwuPgnTestFinish();
