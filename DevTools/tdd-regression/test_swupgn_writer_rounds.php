<?php
// SWU-PGN writer rounds: phase and round boundaries inside the actions that cross them, and keyframes that agree with
// the fold. Plays a real PvP game (SWUSetupGame) through setup, round 1 and into round 2, one recorded frame per request.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 DevTools/tdd-regression/test_swupgn_writer_rounds.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
chdir('/var/www/html/TCGEngine');
if (!function_exists('ConvertMzIDToAbsolute')) { function ConvertMzIDToAbsolute($m, $p): string { return ''; } }
foreach (['DeterministicRNG', 'CoreZoneModifiers', 'GameAuth', 'HTTPLibraries', 'NetworkingLibraries'] as $f) @include_once "./Core/$f.php";
include_once './SWUSim/ZoneClasses.php'; include_once './SWUSim/ZoneAccessors.php';
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php'; include_once './SWUSim/GamestateParser.php';
include_once './SWUSim/CreateGame.php';
foreach (['Assertions', 'Cards', 'CommonSetup', 'GameStateBuilder', 'GameTestAdapter'] as $f) include_once "./SWUSim/Tests/Framework/$f.php";
require_once __DIR__ . '/fixtures/swupgn_test_helpers.php';
global $gameName, $playerID;

class _PgnRoundsPlayer {
    private $seat;
    public function __construct($seat) { $this->seat = $seat; }
    public function getGamePlayerID() { return $this->seat; }
    public function setGamePlayerID($s) { $this->seat = $s; }
    public function getAuthKey() { return 'k' . $this->seat . getmypid(); }
    public function getDeckLink() { return ''; }
    public function getPreconstructedDeck() { return ''; }
    public function getUserId() { return null; }
}
$mk = function (array $spec) { $o = []; foreach ($spec as $id => $n) for ($i = 0; $i < $n; $i++) $o[] = $id; return $o; };
$deck = ['success' => true, 'leader' => 'SOR_005', 'base' => 'SOR_026', 'sideboard' => [],
    'mainDeck' => $mk(['SOR_237' => 10, 'SOR_046' => 10, 'SOR_101' => 10, 'SOR_102' => 10, 'SOR_103' => 10])];
$l = new stdClass(); $l->format = 'premier'; $l->isPrivate = false; $l->players = [new _PgnRoundsPlayer(1), new _PgnRoundsPlayer(2)];
ob_start();
try { $g = (string)SWUSetupGame($l, ['forcedFirstPlayer' => 1, 'resolvedDecks' => [1 => $deck, 2 => $deck], 'rngSeed' => 'pgn-rounds']); }
finally { ob_end_clean(); }
$gameName = $g;
SwuPgnTestCheck(SwuPgnRecIsRecording($g), 'the PvP game is recording');
$a = new GameTestAdapter();
// A Millennium Falcon dropped into seat 1's space arena by the test (in a recorded frame, so the file sees it arrive —
// one unannounced move): her "pay 1 or return her to hand" prompt comes from the ready step, after the round counter
// has moved.
SwuPgnRecFrameBegin();
$space = &GetSpaceArena(1);
$falcon = new SpaceArena('SOR_193'); $falcon->Status = 1; $falcon->Damage = 0; $falcon->Owner = 1; $falcon->Controller = 1;
$falcon->UniqueID = 9001; $falcon->Subcards = []; $falcon->TurnEffects = [];
$space[] = $falcon; unset($space);
SwuPgnRecFrameCommit();
$injectedGaps = intval(SwuPgnStateRead($g)['gaps'] ?? 0);

// One request = one recorded frame, as GameLogBeginFrame/GameLogCommitFrame bracket it in production.
$frame = function (callable $fn) {
    SwuPgnRecFrameBegin();
    ob_start();
    try { $fn(); } finally { ob_end_clean(); }
    SwuPgnRecFrameCommit();
};
$head = function (int $seat) { foreach (GetDecisionQueue($seat) as $d) if (empty($d->removed)) return $d; return null; };
// Answer whatever the game asks, keep every hand, resource the first cards offered, then pass until round 2's action
// phase has seen one pass.
$round2Passes = 0; $played = false; $attacked = false; $claimedR1 = false;
for ($step = 0; $step < 200; $step++) {
    $acted = false;
    foreach ([1, 2] as $seat) {
        $d = $head($seat);
        if ($d === null) continue;
        $type = strval($d->Type ?? '');
        $param = strval($d->Param ?? '');
        $ans = match ($type) {
            'YESNO' => str_contains(strval($d->Tooltip ?? ''), 'Millennium_Falcon') ? 'YES' : 'NO',
            'MZMULTICHOOSE' => (function () use ($param) { [$min] = explode('|', $param); return implode('&', array_map(fn($i) => "myHand-$i", range(0, max(0, intval($min) - 1)))); })(),
            'MZMAYCHOOSE', 'MZCHOOSE' => explode('&', $param)[0] === 'myHand' ? 'myHand-0' : explode('&', $param)[0],
            'OPTIONCHOOSE' => explode('&', $param)[0],
            default => null,
        };
        if ($ans === null) continue;
        $frame(fn() => $a->answerDecision($seat, $ans));
        $acted = true;
        break;
    }
    if ($acted) continue;
    if (intval(GetTurnNumber()) >= 2 && GetCurrentPhase() === 'MAIN' && $round2Passes >= 1 && $attacked) break;
    if (GetCurrentPhase() !== 'MAIN') break;   // stuck outside the action phase with nothing to answer
    $tp = intval(GetTurnPlayer());
    $round = intval(GetTurnNumber());
    // Round 1: seat 1 plays a Alliance X-Wing. Round 2: it attacks the enemy base. Otherwise pass.
    if ($tp === 1 && $round === 1 && !$played) {
        $played = true;
        $idx = null; foreach (GetHand(1) as $i => $o) if (empty($o->removed) && $o->CardID === 'SOR_237' && $idx === null) $idx = $i;   // raw index: myHand-N counts tombstones
        if ($idx !== null) { $frame(fn() => $a->playCardFromHand(1, $idx)); continue; }
    }
    if ($tp === 1 && $round === 2 && !$attacked) {
        $attacked = true;
        foreach (GetSpaceArena(1) as $i => $o) {
            if (!empty($o->removed)) continue;
            if ($o->CardID === 'SOR_237' && intval($o->Status) === 1) { $frame(fn() => $a->declareAttack(1, "mySpaceArena-$i", 'theirBase-0')); continue 2; }
        }
    }
    // Round 1: seat 2 claims the initiative, so round 2's holder (2) is not the seat that passed last (1) — ROUND_START's
    // active must be the holder, not the stale turn player.
    if ($tp === 2 && $round === 1 && !$claimedR1) { $claimedR1 = true; $frame(fn() => $a->takeInitiative(2)); continue; }
    if ($round >= 2) $round2Passes++;
    $frame(fn() => $a->passAction($tp));
}
SwuPgnTestCheck(intval(GetTurnNumber()) === 2, 'the game reached round 2', 'round ' . GetTurnNumber() . ' phase ' . GetCurrentPhase());

$ev = SwuPgnEventsRead($g);
$bySeq = []; foreach ($ev as $e) $bySeq[$e['seq']] = $e;
$seqs = array_keys($bySeq);
SwuPgnTestEq(count($seqs), count($ev), 'every seq is unique');
foreach (['R0.S.start', 'R0.S.end', 'R1.start', 'R1.A.start', 'R1.A.end', 'R1.G.start', 'R1.G.end', 'R1.end', 'R2.start', 'R2.A.start'] as $s) {
    SwuPgnTestCheck(isset($bySeq[$s]), "the boundary record $s is written", implode(' ', array_slice(array_filter($seqs, fn($x) => !preg_match('/\.\d+[a-z]*$/', $x)), 0, 30)));
}
$order = fn(string $s) => array_search($s, $seqs, true);
SwuPgnTestCheck($order('R0.S.end') < $order('R1.start') && $order('R1.start') < $order('R1.A.start') && $order('R1.A.end') < $order('R1.G.start')
    && $order('R1.G.end') < $order('R1.end') && $order('R1.end') < $order('R2.start') && $order('R2.start') < $order('R2.A.start'),
    'the boundaries are in order');
foreach (['R1.start', 'R1.end', 'R2.start'] as $s) {
    $kf = $bySeq[$s]['keyframe'] ?? null;
    SwuPgnTestCheck(is_array($kf) && isset($kf['players'][1], $kf['players'][2]), "$s carries a keyframe with both seats");
}
SwuPgnTestEq([$bySeq['R1.end']['round'] ?? null, $bySeq['R2.start']['round'] ?? null], [1, 2], 'ROUND_END / ROUND_START carry their round');
SwuPgnTestEq($bySeq['R1.end']['keyframe']['phase'] ?? null, 'regroup', 'the R1.end keyframe says phase "regroup"');
$claimed = (bool)array_filter($ev, fn($e) => $e['t'] === 'CLAIM_INITIATIVE' && str_starts_with($e['seq'], 'R1.'));
SwuPgnTestEq($bySeq['R1.end']['keyframe']['initiativeTaken'] ?? null, $claimed, 'the R1.end keyframe states the round\'s initiativeTaken');
// The ready step's own work (the Falcon's upkeep) is round 1's: nothing of round 1 comes after R1.end.
$late = array_filter(array_slice($ev, $order('R1.end') + 1), fn($e) => str_starts_with($e['seq'], 'R1.'));
SwuPgnTestEq(array_values(array_map(fn($e) => $e['seq'] . ' ' . $e['t'], $late)), [], 'no round-1 record follows R1.end');
SwuPgnTestCheck((bool)array_filter($ev, fn($e) => $e['t'] === 'EXHAUST_RESOURCES' && str_starts_with($e['seq'], 'R1.G.')), 'the Falcon\'s upkeep is paid in round 1\'s regroup');
// The regroup's draws are regroup records, not consequences of the final PASS.
$draws = array_filter($ev, fn($e) => $e['t'] === 'MOVE' && $e['from'] === 'deck' && $e['to'] === 'hand' && str_starts_with($e['seq'], 'R1.'));
SwuPgnTestCheck(count($draws) >= 4 && !array_filter($draws, fn($e) => !str_starts_with($e['seq'], 'R1.G.')), 'round 1\'s draws all sit under R1.G.*',
    implode(' ', array_map(fn($e) => $e['seq'], $draws)));
$marine = NULL;
foreach ($bySeq['R2.start']['keyframe']['players'][1]['cards'] ?? [] as $c) if (str_starts_with($c['id'], 'SOR#237')) $marine = $c;
SwuPgnTestCheck($marine !== null && isset($marine['power'], $marine['hp']), 'the round-2 keyframe holds the X-Wing played in round 1, with its stats');
SwuPgnTestCheck((bool)array_filter($ev, fn($e) => $e['t'] === 'ATTACK' && str_starts_with($e['seq'], 'R2.A.')), 'round 2 has the attack');
// §9.1: a player's own play / attack / pass is a numbered step (nothing was resolving when it was made).
$own = array_values(array_filter($ev, fn($e) => in_array($e['t'], ['PLAY', 'ATTACK', 'PASS', 'CLAIM_INITIATIVE'], true) && str_contains($e['seq'], '.A.')));
SwuPgnTestCheck(count($own) >= 4 && !array_filter($own, fn($e) => !preg_match('/\.A\.\d+$/', $e['seq'])), 'every own PLAY / ATTACK / PASS is numbered',
    implode(' ', array_map(fn($e) => $e['seq'] . ' ' . $e['t'], $own)));
// §10.1 [W-46]: every ROUND_START states `active` — the round's initiative holder — and its keyframe agrees; the action
// phase's PHASE_START states it too.
foreach (['R1.start' => 'R1.A.start', 'R2.start' => 'R2.A.start'] as $rs => $ps) {
    $kf = $bySeq[$rs]['keyframe'] ?? [];
    SwuPgnTestCheck(in_array($bySeq[$rs]['active'] ?? null, [1, 2], true) && ($bySeq[$rs]['active'] ?? null) === ($kf['initiative'] ?? null)
        && ($kf['active'] ?? null) === $bySeq[$rs]['active'] && ($bySeq[$ps]['active'] ?? null) === $bySeq[$rs]['active'],
        "$rs states active = the initiative holder (keyframe and $ps agree)", json_encode([$bySeq[$rs]['active'] ?? null, $kf['initiative'] ?? null, $kf['active'] ?? null, $bySeq[$ps]['active'] ?? null]));
}
// §10.1 [W-45]: a card resourced in the regroup phase enters the row exhausted (CR 5.5.1.c) — `exhausted: true` on its MOVE,
// and no EXHAUST_RESOURCES for it; the ready step then readies it (one READY_RESOURCES run per seat).
$regroupIn = array_values(array_filter($ev, fn($e) => $e['t'] === 'MOVE' && ($e['to'] ?? '') === 'resource' && str_starts_with($e['seq'], 'R1.G.')));
SwuPgnTestCheck(count($regroupIn) === 2 && !array_filter($regroupIn, fn($e) => ($e['exhausted'] ?? null) !== true),
    'both regroup resources enter with exhausted: true', json_encode($regroupIn));
$regroupEx = array_filter($ev, fn($e) => $e['t'] === 'EXHAUST_RESOURCES' && str_starts_with($e['seq'], 'R1.G.'));
SwuPgnTestEq(array_sum(array_column($regroupEx, 'amount')), 1, '…and the only regroup EXHAUST_RESOURCES is the Falcon\'s upkeep (1)');
foreach ([1 => 1, 2 => 0] as $s => $n) SwuPgnTestEq($bySeq['R2.start']['keyframe']['players'][$s]['resourcesExhausted'] ?? null, $n, "seat $s's row is ready at R2.start but for the Falcon's upkeep ($n)");
// §13 [W-57]: every keyframe seat carries foreignResources (empty here), every card its traits.
foreach (['R1.start', 'R1.end', 'R2.start'] as $s) {
    foreach ([1, 2] as $p) {
        $seat = $bySeq[$s]['keyframe']['players'][$p] ?? [];
        SwuPgnTestCheck(($seat['foreignResources'] ?? null) === [] && !array_key_exists('effects', $bySeq[$s]['keyframe']) && !array_key_exists('abilityUses', $seat),
            "$s seat $p: foreignResources [] (effects / abilityUses omitted — not read)");
    }
}
SwuPgnTestEq($marine['traits'] ?? null, ['fighter', 'rebel', 'vehicle'], 'the R2.start keyframe card carries its traits (lower case, sorted)');
$xwStats = array_values(array_filter($ev, fn($e) => $e['t'] === 'STATS' && str_starts_with($e['card'], 'SOR#237')));
SwuPgnTestCheck($xwStats && !array_filter($xwStats, fn($e) => ($e['traits'] ?? null) !== ['fighter', 'rebel', 'vehicle']), '[W-55] every STATS carries traits', json_encode($xwStats));
// The fold agrees with every keyframe.
$doc = SwuPgnParse(SwuPgnTestFile($ev));
SwuPgnTestEq(array_slice(SwuPgnValidate(['headers' => SwuPgnTestHeaderTags()] + $doc)['errors'], 0, 3), [], 'the recorded events validate (keyframes keep their object fields)');
$check = SwuPgnCheckKeyframes($doc['events']);
SwuPgnTestCheck(!empty($check['ok']), 'SwuPgnCheckKeyframes: the fold matches every keyframe', json_encode(array_slice($check['mismatches'] ?? [], 0, 6)));
SwuPgnTestEq(intval(SwuPgnStateRead($g)['gaps'] ?? -1) - $injectedGaps, 0, 'the game itself left no announcement gaps (only the test\'s Falcon drop)');

exec('rm -rf ' . escapeshellarg('./SWUSim/Games/' . $g));
SwuPgnTestFinish();
