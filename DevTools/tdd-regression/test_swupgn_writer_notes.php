<?php
// SWU-PGN writer notes: each game-log announcement leaves a note, so the recorded events carry their sources and
// reasons and a fully announced action leaves no gaps. Every scenario runs the engine's own entry points inside a
// recorded frame (SwuPgnRecFrameBegin/Commit — what GameLogBeginFrame/GameLogCommitFrame call per request).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 DevTools/tdd-regression/test_swupgn_writer_notes.php
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

$games = [];
// A recorded fixture game: CommonSetup + $build, recording forced on. Returns the adapter.
function NGame(callable $build, array $my = [], array $their = []): GameTestAdapter {
    global $gameName, $games;
    $gameName = 'swupgnnotes_' . count($games) . '_' . getmypid();
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
// Run $fn as one recorded request; returns [the events it added, the gaps it added].
function NFrame(callable $fn): array {
    global $gameName;
    $n = count(SwuPgnEventsRead($gameName));
    $gaps = intval(SwuPgnStateRead($gameName)['gaps'] ?? 0);
    SwuPgnRecFrameBegin();
    ob_start();
    try { $fn(); } finally { ob_end_clean(); }
    SwuPgnRecFrameCommit();
    $st = SwuPgnStateRead($gameName);
    return [array_slice(SwuPgnEventsRead($gameName), $n), intval($st['gaps'] ?? 0) - $gaps];
}
function NFind(array $events, string $t, array $where = []): ?array {
    foreach ($events as $e) {
        if ($e['t'] !== $t) continue;
        foreach ($where as $k => $v) if (($e[$k] ?? null) !== $v) continue 2;
        return $e;
    }
    return null;
}
function NCopy(int $uid): ?string { global $gameName; return SwuPgnLedgerByUid(SwuPgnStateRead($gameName)['ledger'], $uid); }
function NUid(int $seat, string $arena, string $cardID): int {
    foreach (array_merge(GetGroundArena($seat), GetSpaceArena($seat)) as $u) if (empty($u->removed) && $u->CardID === $cardID) return intval($u->UniqueID);
    return 0;
}
function NTypes(array $ev): string { return implode(',', array_map(fn($e) => $e['t'], $ev)); }

// ── PLAY: a unit from hand ───────────────────────────────────────────────────────────────────────
$g = NGame(fn($b) => $b->WithCardInHandForPlayer(1, 'SOR_095'));
[$ev, $gaps] = NFrame(fn() => $g->playCardFromHand(1, 0));
$copy = NCopy(NUid(1, 'ground', 'SOR_095'));
SwuPgnTestCheck(NFind($ev, 'PLAY', ['p' => 1, 'card' => $copy, 'zone' => 'ground', 'cost' => 2]) !== null, 'PLAY names the played copy and its printed cost', NTypes($ev));
SwuPgnTestEq($gaps, 0, 'a unit play leaves no gaps');

// ── PLAY upgrade: the attach lands in the next request (after the target pick) ──────────────────
$g = NGame(fn($b) => $b->WithCardInHandForPlayer(1, 'SOR_120')->WithGroundUnitForPlayer(1, 'SOR_095')->WithGroundUnitForPlayer(1, 'SOR_046'));
[$ev1, $gaps1] = NFrame(fn() => $g->playCardFromHand(1, 0));
[$ev2, $gaps2] = NFrame(fn() => $g->answerDecision(1, 'myGroundArena-0'));
$ev = array_merge($ev1, $ev2);
$host = NCopy(NUid(1, 'ground', 'SOR_095'));
SwuPgnTestCheck(NFind($ev, 'PLAY_UPGRADE', ['p' => 1, 'target' => $host]) !== null, 'PLAY_UPGRADE names its host', NTypes($ev));
SwuPgnTestCheck(NFind($ev, 'MOVE', ['kind' => 'upgrade', 'attachedTo' => $host]) !== null, 'the upgrade MOVE carries attachedTo');
SwuPgnTestCheck(NFind($ev, 'CHOICE', ['p' => 1]) !== null, 'the host pick is a CHOICE');
SwuPgnTestEq($gaps1 + $gaps2, 0, 'an upgrade play leaves no gaps');

// ── ATTACK a unit: combat damage both ways, the defender defeated ────────────────────────────────
$g = NGame(fn($b) => $b->WithGroundUnitForPlayer(1, 'SOR_046')->WithGroundUnitForPlayer(2, 'SOR_095'));
$atk = NCopy(NUid(1, 'ground', 'SOR_046')); $def = NCopy(NUid(2, 'ground', 'SOR_095'));
[$ev, $gaps] = NFrame(fn() => $g->declareAttack(1, 'myGroundArena-0', 'theirGroundArena-0'));
SwuPgnTestCheck(NFind($ev, 'ATTACK', ['p' => 1, 'atk' => $atk, 'def' => $def, 'defenderType' => 'unit']) !== null, 'ATTACK names attacker and defender', NTypes($ev));
SwuPgnTestCheck(NFind($ev, 'DAMAGE', ['src' => $atk, 'tgt' => $def, 'damageType' => 'combat']) !== null, 'combat DAMAGE src = the attacker\'s copy');
SwuPgnTestCheck(NFind($ev, 'DAMAGE', ['src' => $def, 'tgt' => $atk, 'damageType' => 'combat']) !== null, 'the counter-damage src = the defender\'s copy');
SwuPgnTestCheck(NFind($ev, 'DEFEAT', ['card' => $def, 'reason' => 'attack', 'defeatedBy' => $atk]) !== null, 'DEFEAT reason "attack", defeatedBy the attacker');
SwuPgnTestEq($gaps, 0, 'a unit attack leaves no gaps');

// ── ATTACK a base, with Restore healing the attacker's base ──────────────────────────────────────
$g = NGame(fn($b) => $b->WithSpaceUnitForPlayer(1, 'SOR_044'), ['baseDamage' => 5]);
$atk = NCopy(NUid(1, 'space', 'SOR_044'));
[$ev, $gaps] = NFrame(fn() => $g->declareAttack(1, 'mySpaceArena-0', 'theirBase-0'));
SwuPgnTestCheck(NFind($ev, 'ATTACK', ['atk' => $atk, 'def' => 'base@2', 'defenderType' => 'base']) !== null, 'a base ATTACK', NTypes($ev));
SwuPgnTestCheck(NFind($ev, 'DAMAGE', ['src' => $atk, 'tgt' => 'base@2', 'damageType' => 'combat']) !== null, 'base combat DAMAGE src = the attacker');
SwuPgnTestCheck(NFind($ev, 'HEAL', ['tgt' => 'base@1']) !== null, 'Restore is a HEAL on the attacker\'s base');
SwuPgnTestEq($gaps, 0, 'a base attack with Restore leaves no gaps');

// ── Ability DAMAGE (Open Fire) and an effect DEFEAT (Vanquish) ───────────────────────────────────
$g = NGame(fn($b) => $b->WithCardInHandForPlayer(1, 'SOR_172')->WithGroundUnitForPlayer(2, 'SOR_046')->WithGroundUnitForPlayer(2, 'SOR_049'));
$tgt = NCopy(NUid(2, 'ground', 'SOR_046'));
[$ev1, $g1] = NFrame(fn() => $g->playCardFromHand(1, 0));
[$ev2, $g2] = NFrame(fn() => $g->answerDecision(1, 'theirGroundArena-0'));
$ev = array_merge($ev1, $ev2);
$event = NFind($ev, 'PLAY_EVENT', ['p' => 1]);
SwuPgnTestCheck($event !== null && NFind($ev, 'DAMAGE', ['src' => $event['card'], 'tgt' => $tgt, 'amt' => 4, 'damageType' => 'ability']) !== null,
    'ability DAMAGE src = the event card\'s copy', NTypes($ev));
SwuPgnTestEq($g1 + $g2, 0, 'an event that deals damage leaves no gaps');

$g = NGame(fn($b) => $b->WithCardInHandForPlayer(1, 'SOR_078')->WithGroundUnitForPlayer(2, 'SOR_046')->WithGroundUnitForPlayer(2, 'SOR_049'));
$tgt = NCopy(NUid(2, 'ground', 'SOR_046'));
[$ev1, $g1] = NFrame(fn() => $g->playCardFromHand(1, 0));
[$ev2, $g2] = NFrame(fn() => $g->answerDecision(1, 'theirGroundArena-0'));
$ev = array_merge($ev1, $ev2);
$event = NFind($ev, 'PLAY_EVENT', ['p' => 1]);
SwuPgnTestCheck($event !== null && NFind($ev, 'DEFEAT', ['card' => $tgt, 'reason' => 'ability', 'defeatedBy' => $event['card']]) !== null,
    'an effect DEFEAT: reason "ability", defeatedBy the event card', NTypes($ev));
SwuPgnTestEq($g1 + $g2, 0, 'an event that defeats leaves no gaps');

// ── HEAL from an event (Repair) ──────────────────────────────────────────────────────────────────
$g = NGame(fn($b) => $b->WithCardInHandForPlayer(1, 'SOR_074'), ['baseDamage' => 6]);
[$ev1, $g1] = NFrame(fn() => $g->playCardFromHand(1, 0));
[$ev2, $g2] = NFrame(fn() => $g->answerDecision(1, 'myBase-0'));
$ev = array_merge($ev1, $ev2);
$event = NFind($ev, 'PLAY_EVENT', ['p' => 1]);
SwuPgnTestCheck($event !== null && NFind($ev, 'HEAL', ['src' => $event['card'], 'tgt' => 'base@1', 'amt' => 3]) !== null, 'HEAL src = the event card', NTypes($ev));
SwuPgnTestEq($g1 + $g2, 0, 'a heal leaves no gaps');

// ── TOKEN: a Shielded unit gains its Shield; a Shield is used against an attack ──────────────────
$g = NGame(fn($b) => $b->WithCardInHandForPlayer(1, 'SOR_180'));
[$ev, $gaps] = NFrame(fn() => $g->playCardFromHand(1, 0));
$host = NCopy(NUid(1, 'ground', 'SOR_180'));
SwuPgnTestCheck(NFind($ev, 'SHIELD_GAIN', ['card' => $host]) !== null && NFind($ev, 'MOVE', ['from' => 'outsideTheGame', 'attachedTo' => $host]) !== null,
    'Shielded: the token MOVE in and SHIELD_GAIN on the host', NTypes($ev));
SwuPgnTestEq($gaps, 0, 'a Shielded play leaves no gaps');
SwuPgnTestCheck(NFind($ev, 'TRIGGER', ['p' => 1]) !== null, 'the Shielded keyword fires as a TRIGGER');
$g = NGame(function ($b) {
    $b->WithGroundUnitForPlayer(1, 'SOR_046')->WithGroundUnitForPlayer(2, 'SOR_095');
    $b->WithUpgradesOnGroundUnitForPlayer(2, 0, [GameStateBuilder::Upgrade('SOR_T02', 2)]);
});
$def = NCopy(NUid(2, 'ground', 'SOR_095'));
[$ev, $gaps] = NFrame(fn() => $g->declareAttack(1, 'myGroundArena-0', 'theirGroundArena-0'));
SwuPgnTestCheck(NFind($ev, 'SHIELD_USE', ['card' => $def]) !== null && NFind($ev, 'MOVE', ['to' => 'outsideTheGame']) !== null,
    'a used Shield: SHIELD_USE then the token MOVE out', NTypes($ev));
SwuPgnTestEq($gaps, 0, 'a Shield used in combat leaves no gaps');

// ── DRAW, deck-out, RESOURCE ─────────────────────────────────────────────────────────────────────
$g = NGame(fn($b) => null);
[$ev, $gaps] = NFrame(fn() => DoDrawCard(1, 2));
SwuPgnTestCheck(NFind($ev, 'DRAW', ['p' => 1, 'count' => 2]) !== null, 'DRAW count 2', NTypes($ev));
SwuPgnTestEq($gaps, 0, 'a draw leaves no gaps');
[$ev, $gaps] = NFrame(fn() => DoDrawCard(1, 4));      // 2 left: two cards, then two deck-out hits
$hit = NFind($ev, 'DAMAGE', ['tgt' => 'base@1']);
SwuPgnTestCheck($hit !== null && !array_key_exists('src', $hit), 'deck-out damage has no src (the rules dealt it)', NTypes($ev));
SwuPgnTestEq($gaps, 0, 'a deck-out leaves no gaps');
$g = NGame(fn($b) => $b->WithCardInHandForPlayer(1, 'SOR_095'));
[$ev, $gaps] = NFrame(fn() => $g->resourceCard(1, 0));
$res = NFind($ev, 'RESOURCE', ['p' => 1]);
SwuPgnTestCheck($res !== null && NFind($ev, 'MOVE', ['card' => $res['card'], 'to' => 'resource']) !== null, 'RESOURCE with its MOVE', NTypes($ev));
SwuPgnTestEq($gaps, 0, 'resourcing leaves no gaps');

// ── PASS, CLAIM, DEPLOY ──────────────────────────────────────────────────────────────────────────
$g = NGame(fn($b) => null);
[$ev, $gaps] = NFrame(fn() => $g->passAction(1));
SwuPgnTestCheck(NFind($ev, 'PASS', ['p' => 1]) !== null, 'PASS', NTypes($ev));
[$ev, $gaps] = NFrame(fn() => $g->takeInitiative(2));
SwuPgnTestCheck(NFind($ev, 'CLAIM_INITIATIVE', ['p' => 2]) !== null, 'CLAIM_INITIATIVE', NTypes($ev));
SwuPgnTestEq($gaps, 0, 'a claim leaves no gaps');
$g = NGame(fn($b) => null);
[$ev, $gaps] = NFrame(fn() => $g->deployLeader(1));
$dep = NFind($ev, 'DEPLOY_LEADER', ['p' => 1]);
SwuPgnTestCheck($dep !== null && NFind($ev, 'MOVE', ['card' => $dep['card'], 'from' => 'base', 'to' => 'ground']) !== null, 'DEPLOY_LEADER with its MOVE', NTypes($ev));
SwuPgnTestEq($gaps, 0, 'a deploy leaves no gaps');

// ── CONTROL (Change of Heart) and CAPTURE (Take Captive) ─────────────────────────────────────────
$g = NGame(fn($b) => $b->WithCardInHandForPlayer(1, 'SOR_224')->WithGroundUnitForPlayer(2, 'SOR_046')->WithGroundUnitForPlayer(2, 'SOR_049'));
$tgt = NCopy(NUid(2, 'ground', 'SOR_046'));
[$ev1, $g1] = NFrame(fn() => $g->playCardFromHand(1, 0));
[$ev2, $g2] = NFrame(fn() => $g->answerDecision(1, 'theirGroundArena-0'));
$ev = array_merge($ev1, $ev2);
SwuPgnTestCheck(NFind($ev, 'TAKE_CONTROL', ['p' => 1, 'card' => $tgt]) !== null, 'TAKE_CONTROL', NTypes($ev));
SwuPgnTestEq($g1 + $g2, 0, 'a take control leaves no gaps');

$g = NGame(fn($b) => $b->WithCardInHandForPlayer(1, 'SHD_131')->WithGroundUnitForPlayer(1, 'SOR_049')->WithGroundUnitForPlayer(2, 'SOR_046')->WithGroundUnitForPlayer(2, 'SOR_095'));
$captor = NCopy(NUid(1, 'ground', 'SOR_049')); $tgt = NCopy(NUid(2, 'ground', 'SOR_095'));
$all = []; $gsum = 0;
[$e, $gg] = NFrame(fn() => $g->playCardFromHand(1, 0)); $all = array_merge($all, $e); $gsum += $gg;
for ($i = 0; $i < 3; $i++) {   // answer whatever the event asks: captor first, then the captive
    $head = null; foreach (GetDecisionQueue(1) as $d) if (empty($d->removed)) { $head = $d; break; }
    if ($head === null || ($head->Type ?? '') !== 'MZCHOOSE') break;
    $opts = explode('&', (string)$head->Param);
    $pick = in_array('theirGroundArena-1', $opts, true) ? 'theirGroundArena-1' : (in_array('myGroundArena-0', $opts, true) ? 'myGroundArena-0' : $opts[0]);
    [$e, $gg] = NFrame(fn() => $g->answerDecision(1, $pick)); $all = array_merge($all, $e); $gsum += $gg;
}
SwuPgnTestCheck(NFind($all, 'CAPTURE', ['p' => 1, 'card' => $tgt, 'by' => $captor]) !== null && NFind($all, 'MOVE', ['card' => $tgt, 'to' => 'capture']) !== null,
    'CAPTURE by the captor, with its MOVE', NTypes($all));
SwuPgnTestEq($gsum, 0, 'a capture leaves no gaps');

// RESCUE: the captor leaves play and its captive comes back.
$rescued = $tgt;
[$ev, $gaps] = NFrame(function () { global $playerID; $playerID = 1; SWUDefeatUnit(1, 'myGroundArena-0'); });
SwuPgnTestCheck(NFind($ev, 'RESCUE', ['p' => 2, 'card' => $rescued]) !== null && NFind($ev, 'MOVE', ['card' => $rescued, 'from' => 'capture']) !== null,
    'RESCUE: the same copy comes back out of capture', NTypes($ev));
SwuPgnTestEq($gaps, 0, 'a rescue leaves no gaps');

// ── ABILITY + FLIP: TWI_017's Heroism face (draw 1, heal 2, flip) ────────────────────────────────
$g = NGame(fn($b) => $b->WithGlobalEffectForPlayer(1, 'SWU_FRIENDLY_HEROISM_DEFEATED'), ['leaderCardID' => 'TWI_017', 'baseDamage' => 4]);
[$ev, $gaps] = NFrame(fn() => $g->useLeaderAbility(1));
SwuPgnTestCheck(NFind($ev, 'ABILITY_ACTIVATE', ['p' => 1, 'kind' => 'action']) !== null, 'ABILITY_ACTIVATE kind "action"', NTypes($ev));
SwuPgnTestCheck(NFind($ev, 'LEADER_FLIP', ['p' => 1, 'onStartingSide' => false]) !== null, 'LEADER_FLIP to the back face');
SwuPgnTestCheck(NFind($ev, 'DEPLOY_LEADER') === null && NFind($ev, 'MOVE', ['to' => 'outsideTheGame']) === null, 'a flip is neither a deploy nor a move');
SwuPgnTestEq($gaps, 0, 'a leader action with a flip leaves no gaps');

// ── KEEP, DISCARD, SEARCH ────────────────────────────────────────────────────────────────────────
$g = NGame(fn($b) => $b->WithCardInHandForPlayer(1, 'SOR_095'));
[$ev, $gaps] = NFrame(function () { global $customDQHandlers; $customDQHandlers['MulliganDecision'](1, [1], 'NO'); });
SwuPgnTestCheck(NFind($ev, 'KEEP_HAND', ['p' => 1]) !== null, 'KEEP_HAND', NTypes($ev));
[$ev, $gaps] = NFrame(fn() => DoDiscardCard(1, 'myHand-0'));
$d = NFind($ev, 'DISCARD', ['p' => 1]);
SwuPgnTestCheck($d !== null && NFind($ev, 'MOVE', ['card' => $d['cards'][0] ?? '', 'from' => 'hand', 'to' => 'discard']) !== null, 'DISCARD with its MOVE', NTypes($ev));
SwuPgnTestEq($gaps, 0, 'a discard leaves no gaps');
$g = NGame(fn($b) => $b->WithCardInHandForPlayer(1, 'SOR_042'));
[$ev1, $g1] = NFrame(fn() => $g->playCardFromHand(1, 0));
$head = null; foreach (GetDecisionQueue(1) as $d) if (empty($d->removed)) { $head = $d; break; }
$pick = $head !== null ? explode('&', (string)$head->Param)[0] : '';
[$ev2, $g2] = NFrame(fn() => $g->answerDecision(1, $pick));
$ev = array_merge($ev1, $ev2);
SwuPgnTestCheck(NFind($ev, 'SEARCH', ['p' => 1]) !== null && NFind($ev, 'MOVE', ['from' => 'deck', 'to' => 'hand']) !== null, 'SEARCH, then the searched card\'s MOVE', NTypes($ev));
SwuPgnTestEq($g1 + $g2, 0, 'a search leaves no gaps');
$se = NFind($ev, 'SEARCH', ['p' => 1]) ?? [];
SwuPgnTestCheck(($se['owner'] ?? null) === 1 && count($se['seen'] ?? []) >= 2 && count(array_unique($se['seen'])) === count($se['seen'])
    && in_array(NFind($ev, 'MOVE', ['from' => 'deck', 'to' => 'hand'])['card'] ?? '-', $se['seen'], true),
    '§10.2 [W-50]: SEARCH owner 1, seen = every card the search showed (distinct copies, the drawn one among them)', json_encode($se));

// ── A mulligan writes the whole hand going back and the whole new hand drawn — even when the same cards come back ──────
$g = NGame(function ($b) { for ($i = 0; $i < 6; $i++) $b->WithCardInHandForPlayer(1, 'SOR_095'); for ($i = 0; $i < 6; $i++) $b->WithCardInDeckForPlayer(1, 'SOR_095'); });
$handBefore = count(array_filter(GetHand(1), fn($o) => empty($o->removed)));
[$ev] = NFrame(function () use ($customDQHandlers) { global $customDQHandlers; $GLOBALS['playerID'] = 1; $customDQHandlers['MulliganDecision'](1, ['1'], 'YES'); });
$back = count(array_filter($ev, fn($e) => $e['t'] === 'MOVE' && ($e['p'] ?? 0) === 1 && $e['from'] === 'hand' && $e['to'] === 'deck'));
$drawn = count(array_filter($ev, fn($e) => $e['t'] === 'MOVE' && ($e['p'] ?? 0) === 1 && $e['from'] === 'deck' && $e['to'] === 'hand'));
$handAfter = count(array_filter(GetHand(1), fn($o) => empty($o->removed)));
SwuPgnTestEq([$back, $drawn], [$handBefore, $handAfter], 'a mulligan of the same cards: every card back to the deck, every card of the new hand drawn');

// ── DEFEAT reasons the rules give (§6.4): the uniqueness rule, the rules engine (no card to blame), Exploit ──────────
$reasonOf = function (array $ev, string $cardID) {
    foreach ($ev as $e) if ($e['t'] === 'DEFEAT' && SwuPgnBaseId($e['card']) === SwuPgnCardRef($cardID)) return [$e['reason'] ?? null, $e['defeatedBy'] ?? null];
    return null;
};
global $playerID, $customDQHandlers;
// The uniqueness choice: a second Darth Vader, the player defeats one.
$g = NGame(fn($b) => $b->WithGroundUnitForPlayer(1, 'SOR_087')->WithGroundUnitForPlayer(1, 'SOR_087'));
[$ev] = NFrame(function () use ($customDQHandlers) { $GLOBALS['playerID'] = 1; $customDQHandlers['UNIQUENESS_DEFEAT'](1, ['1', '1'], 'myGroundArena-0'); });
SwuPgnTestEq($reasonOf($ev, 'SOR_087'), ['uniqueRule', null], 'a copy defeated by the uniqueness rule: reason uniqueRule');
// A unique pilot attaching while its unit form is in play: that copy is defeated automatically.
$g = NGame(fn($b) => $b->WithGroundUnitForPlayer(1, 'JTL_046'));
[$ev] = NFrame(function () { $GLOBALS['playerID'] = 1; SWUEnforceUpgradeUniqueness(1, 'JTL_046', new stdClass()); });
SwuPgnTestEq($reasonOf($ev, 'JTL_046'), ['uniqueRule', null], 'a copy defeated when another attaches: reason uniqueRule');
// No remaining HP, nobody's card: the rules engine.
$g = NGame(fn($b) => $b->WithGroundUnitForPlayer(1, 'SOR_095'));
[$ev] = NFrame(function () { $u = &GetGroundArena(1); $u[0]->Damage = 3; unset($u); SWUCheckShrinkDefeats(); });
SwuPgnTestEq($reasonOf($ev, 'SOR_095'), ['frameworkEffect', null], 'a unit left with no HP and no card to blame: reason frameworkEffect');
// A leader unit that would change control is defeated instead (CR 3.4.6).
$g = NGame(fn($b) => null, ['leaderDeployed' => true]);
$leaderCard = strval(GetLeader(1)[0]->CardID);
[$ev] = NFrame(function () { $GLOBALS['playerID'] = 1; SWUTakeControlOfUnit(2, 'myGroundArena-0'); });
SwuPgnTestEq($reasonOf($ev, $leaderCard), ['frameworkEffect', null], 'a leader unit that would change control: reason frameworkEffect');
// Exploit: the played card's keyword defeated it.
$g = NGame(fn($b) => $b->WithGroundUnitForPlayer(1, 'SOR_095')->WithCardInHandForPlayer(1, 'SOR_046'));
[$ev] = NFrame(function () { $GLOBALS['playerID'] = 1; _SWUResolveExploitPicks(1, 'SOR_046', 'myGroundArena-0', 1, fn() => true); });
$ex = $reasonOf($ev, 'SOR_095');
SwuPgnTestCheck(($ex[0] ?? null) === 'ability' && SwuPgnBaseId(strval($ex[1] ?? '')) === SwuPgnCardRef('SOR_046'), 'a unit exploited: reason ability, defeatedBy the played card', json_encode($ex));

// ── CHOICE notes say whose zone each offered card is in (the matcher names THAT seat's copy) ─────────────────────
$g = NGame(fn($b) => $b->WithCardInHandForPlayer(2, 'SOR_095')->WithCardInHandForPlayer(2, 'SOR_046'));
SwuPgnRecFrameBegin();
SwuPgnNoteChoice(2, (object)['Type' => 'MZCHOOSE', 'Param' => 'myHand-0&myHand-1&theirDeck-0', 'Tooltip' => 'Pick'], 'myHand-0');
$cn = array_values(array_filter($GLOBALS['SWU_PGN_NOTES'] ?? [], fn($n) => ($n['t'] ?? '') === 'CHOICE'));
SwuPgnTestEq([$cn[0]['offeredSeats'] ?? null, $cn[0]['offeredZones'] ?? null], [[2, 2, 1], ['hand', 'hand', 'deck']], 'CHOICE notes carry each offered card\'s seat and zone');
SwuPgnRecFrameCommit();
// An offered upgrade is a host's subcard (".u<key>"): it is named by its card, in its host's zone — a base's own, or the
// unit's attachments — never by the engine's mz string.
$g = NGame(fn($b) => $b->WithUpgradeOnBaseForPlayer(2, 'HMW_095')->WithGroundUnitForPlayer(1, 'SOR_095')->WithUpgradesOnGroundUnitForPlayer(1, 0, [GameStateBuilder::Upgrade('SOR_120', 1)]));
SwuPgnRecFrameBegin();
SwuPgnNoteChoice(1, (object)['Type' => 'MZCHOOSE', 'Param' => 'theirBase-0.u0&myGroundArena-0.u0', 'Tooltip' => 'Choose an upgrade'], 'theirBase-0.u0');
$cn = array_values(array_filter($GLOBALS['SWU_PGN_NOTES'] ?? [], fn($n) => ($n['t'] ?? '') === 'CHOICE'));
$uid = intval(GetGroundArena(1)[0]->UniqueID);
SwuPgnTestEq([$cn[0]['offered'] ?? null, $cn[0]['offeredSeats'] ?? null, $cn[0]['offeredZones'] ?? null],
    [['HMW_095', 'SOR_120'], [2, 1], ['base', "attached:$uid"]], 'an offered upgrade is its card, in its host\'s zone');
SwuPgnRecFrameCommit();

// ── An event played FROM the discard pile (SEC_053 via TWI_201 Aid from the Innocent): it is written ───────────────
$gameName = 'swupgnnotes_discardplay_' . getmypid(); $games[] = $gameName;
@mkdir('./SWUSim/Games/' . $gameName, 0777, true);
$b = new GameStateBuilder();
CommonSetup($b, 'yyw', 'rrk', ['resourceCount' => 8, 'handCardIds' => ['TWI_201']], []);
$b->WithActivePlayer(1)->WithGroundUnitForPlayer(2, 'SOR_140');
foreach (['SEC_053', 'SOR_199', 'SEC_080', 'SEC_080', 'SEC_080'] as $c) $b->WithCardInDeckForPlayer(1, $c);
$g = new GameTestAdapter(); $g->loadState($b);
SwuPgnRecForceForTest($gameName);
NFrame(fn() => $g->playCardFromHand(1, 0));
NFrame(fn() => $g->answerDecision(1, 'SEC_053,SOR_199'));
$di = null; foreach (array_values(array_filter(GetDiscard(1), fn($o) => empty($o->removed))) as $i => $o) if ($o->CardID === 'SEC_053') $di = $i;
[$ev, $gaps] = NFrame(fn() => $g->playFromDiscard(1, intval($di)));
SwuPgnTestCheck(NFind($ev, 'PLAY_EVENT', ['p' => 1, 'card' => SwuPgnCardRef('SEC_053')]) !== null, 'an event played from the discard pile is a PLAY_EVENT', NTypes($ev));
SwuPgnTestEq($gaps, 0, 'and leaves no gaps');

// ── §9.1: a play / attack made while an ability resolves is a lettered sub-step, not a numbered action ──────────────
// Syndicate Lackeys' Ambush: "After you play this unit, it may ready and attack an enemy unit." The attack is the keyword's.
$g = NGame(fn($b) => $b->WithCardInHandForPlayer(1, 'SOR_213'), [], []);
$enemy = new GroundArena('SOR_095'); $enemy->Status = 1; $enemy->Damage = 0; $enemy->Owner = 2; $enemy->Controller = 2; $enemy->UniqueID = 8801; $enemy->Subcards = []; $enemy->TurnEffects = [];
$eg = &GetGroundArena(2); $eg[] = $enemy; unset($eg);
SwuPgnStateWrite($gameName, ['prev' => SwuPgnSnapshot()] + SwuPgnStateRead($gameName));
$ledger = SwuPgnStateRead($gameName)['ledger']; SwuPgnLedgerPut($ledger, 2, 'ground', SwuPgnLedgerNewToken($ledger, 'SOR_095'), 8801);
SwuPgnStateWrite($gameName, ['ledger' => $ledger] + SwuPgnStateRead($gameName));
$all = [];
[$ev, ] = NFrame(fn() => $g->playCardFromHand(1, 0)); $all = array_merge($all, $ev);
for ($i = 0; $i < 6; $i++) {
    $head = null; foreach (GetDecisionQueue(1) as $d) if (empty($d->removed)) { $head = $d; break; }
    if ($head === null) break;
    $ans = match (strval($head->Type)) { 'YESNO' => 'YES', 'OPTIONCHOOSE' => explode('&', strval($head->Param))[0], default => explode('&', strval($head->Param))[0] };
    [$ev, ] = NFrame(fn() => $g->answerDecision(1, $ans)); $all = array_merge($all, $ev);
}
$play = NFind($all, 'PLAY', ['p' => 1]); $atk = NFind($all, 'ATTACK', ['p' => 1]);
SwuPgnTestCheck($play !== null && $atk !== null, 'the Ambush play and its attack are recorded', NTypes($all));
if ($play !== null && $atk !== null) {
    SwuPgnTestCheck(preg_match('/^R\d+\.A\.\d+[a-z]+$/', $atk['seq']) === 1 && str_starts_with($atk['seq'], $play['seq']),
        '§9.1: the Ambush attack is a lettered sub-step of the play (' . $play['seq'] . ' → ' . $atk['seq'] . ')', array_map(fn($e) => $e['seq'] . ' ' . $e['t'], $all));
}

// ── §10.2 [W-51] NAME and [W-52] disclose ─────────────────────────────────────────────────────────
$g = NGame(fn($b) => $b->WithCardInHandForPlayer(1, 'SOR_095'));
DecisionQueueController::AddDecision(1, 'NAMECARD', '', 1, 'Name_a_card');
[$ev, ] = NFrame(fn() => GameOnDecisionAnswered(1, 'battlefield marine'));
SwuPgnTestEq(array_map(fn($e) => array_diff_key($e, ['seq' => 1]), array_values(array_filter($ev, fn($e) => in_array($e['t'], ['NAME', 'CHOICE'], true)))),
    [['t' => 'NAME', 'p' => 1, 'name' => 'Battlefield Marine']], 'naming a card writes a NAME with the printed title (no CHOICE)');
foreach (GetDecisionQueue(1) as $d) $d->removed = true;
$handCopy = SwuPgnStateRead($gameName)['ledger']['zones'][1]['hand'][0] ?? null;
[$ev, ] = NFrame(function () { global $customDQHandlers; $customDQHandlers['DISCLOSE_RESOLVE'](1, ['Command', ''], 'myHand-0'); });
SwuPgnTestEq(array_map(fn($e) => array_diff_key($e, ['seq' => 1]), array_values(array_filter($ev, fn($e) => $e['t'] === 'REVEAL'))),
    [['t' => 'REVEAL', 'p' => 1, 'zone' => 'hand', 'cards' => [$handCopy]]], 'a successful disclose: one REVEAL from hand naming the card shown');
[$ev, ] = NFrame(function () { global $customDQHandlers; $customDQHandlers['DISCLOSE_RESOLVE'](1, ['Vigilance', ''], 'myHand-0'); });
SwuPgnTestEq(array_values(array_filter($ev, fn($e) => $e['t'] === 'REVEAL')), [], 'a disclose that fails (does not cover) writes no REVEAL');

// ── §10.2 [W-50] LOOK: a look at the top of the deck that puts the cards back (scry) ─────────────────
$g = NGame(fn($b) => null);
[$ev, ] = NFrame(fn() => DoScry(2, 2));   // seat 2: its copies of the cards both decks hold are the ":2" ones
$look = NFind($ev, 'LOOK', ['p' => 2]);
SwuPgnTestCheck($look !== null && $look['owner'] === 2 && $look['zone'] === 'deck' && $look['count'] === 2 && count($look['cards']) === 2
    && count(array_intersect($look['cards'], SwuPgnStateRead($gameName)['ledger']['zones'][2]['deck'] ?? [])) === 2,
    'a scry writes a LOOK: p 2, owner 2, deck, the two cards (seat 2\'s own deck copies), count 2', $ev);

foreach ($games as $x) exec('rm -rf ' . escapeshellarg('./SWUSim/Games/' . $x));
SwuPgnTestFinish();
