<?php
// Phase 1a Task 2 — the two enumerator gaps the heuristic rules need closed (SWUSim/BotLegalActions.php):
//   1. "take the initiative" is offered as a free-play action while the initiative is available
//      (rules 3 and 9 of the RL bots spec cannot fire without it);
//   2. a decision carries its type, param, RAW tooltip and the params of the queue entries behind it, so a
//      rule can tell an attack-target prompt from an Ambush YES/NO and see which unit is attacking.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_legal_actions_context_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
$ids = fn($legal) => array_map(fn($a) => strval($a['cardID'] ?? ''), (array)($legal['actions'] ?? []));
$TAKE = 'InitiativeCounter-0!CustomInput!TakeInitiative';
$PASS = 'myHealth-0!CustomInput!Pass';

// Free play, initiative available.
$build(function ($b) { $b->WithGroundUnitForPlayer(1, 'SOR_095', true); });
$cards = $ids(SWUBotLegalActions($gameName, 1));
$check(in_array($TAKE, $cards, true), 'take-initiative is offered while available');
$check(end($cards) === $PASS, 'Pass stays last');
$pos = array_search($TAKE, $cards, true);
$check($pos !== false && $cards[$pos + 1] === $PASS, 'take-initiative sits immediately before Pass');

// Free play, initiative already claimed this round (by the opponent).
$build(function ($b) { $b->WithInitiativePlayerBeing(2); $b->WithInitiativeClaimed(); });
$check(!in_array($TAKE, $ids(SWUBotLegalActions($gameName, 1)), true), 'not offered once claimed');

// Decision context: a two-target attack raises the attack-target prompt.
$build(function ($b) {
    $b->WithGroundUnitForPlayer(1, 'LOF_084', true);   // Knight of Ren 4/4
    $b->WithGroundUnitForPlayer(2, 'SOR_095', true);   // a second target besides the base
});
$raiseAttack(1, 'myGroundArena-0');
$legal = SWUBotLegalActions($gameName, 1);
$check(($legal['kind'] ?? '') === 'decision', 'an attack with two targets raises a decision');
$check(($legal['decisionType'] ?? '') === 'MZCHOOSE', 'decision type passed through');
$check(($legal['decisionTooltip'] ?? '') === 'Choose_an_attack_target', 'RAW tooltip passed through — got ' . json_encode($legal['decisionTooltip'] ?? null));
$check(str_contains(strval($legal['decisionParam'] ?? ''), 'theirGroundArena-0'), 'decision param passed through');
$check(str_starts_with(strval($legal['following'][0] ?? ''), 'SWUResolveAttack|myGroundArena-0'),
    'following[0] names the attacker — got ' . json_encode($legal['following'] ?? null));
$check(count((array)($legal['following'] ?? [])) <= 3, 'following is capped at 3 entries');
$check(in_array('theirBase-0', $ids($legal), true), 'the base is a candidate (mzID form verified: theirBase-0)');

// ── Answer-encoding corrections (real-deck sweep, 2026-09-13) ──────────────────────────────────────
// The shared bridge's encoders (DevTools/TestAutomationBridge.php — out of bounds for bot work) got two prompt
// shapes wrong, so EVERY candidate was refused by the engine. The game stalled or burned retries: 34 and
// ~420 of 18,200 games. BotLegalActions corrects them. Proof: the answer given is ACCEPTED (the prompt advances).
include_once './SWUSim/Custom/BotLookahead.php';
$pending = function (int $seat) { foreach (GetDecisionQueue($seat) as $e) { if ($e !== null && empty($e->removed)) return $e; } return null; };

// 1. MZSPLITASSIGN with a trailing flag: "4|mySpaceArena-0|UPTO". JTL_009 Boba Fett deployed as a Pilot:
//    "Deal up to 4 damage divided as you choose among any number of units."
$build(function ($b) {
    $b->MyLeader('JTL_009'); $b->FillResourcesForPlayer(1, 'SOR_095', 6);
    $b->WithSpaceUnitForPlayer(1, 'SEC_171', true); $b->WithSpaceUnitForPlayer(2, 'JTL_087', true);
});
$act(1, 10001, 'myLeader-0!CustomInput!DeployLeader:Unit');
$act(1, 100, 'Pilot');
$legal = SWUBotLegalActions($gameName, 1);
$check(($legal['decisionType'] ?? '') === 'MZSPLITASSIGN' && str_ends_with(strval($legal['decisionParam'] ?? ''), '|UPTO'),
    'fixture: the up-to split prompt — ' . strval($legal['decisionParam'] ?? ''));
$check(!empty($ids($legal)) && !array_filter($ids($legal), fn($c) => str_contains($c, '|')), 'no candidate carries the |UPTO flag inside a target');
// Answer with a split that uses the target the flag had been glued to (the LAST spec, theirSpaceArena-0).
$first = array_values(array_filter($ids($legal), fn($c) => str_contains($c, 'theirSpaceArena-0')))[0] ?? '';
$act(1, 100, $first);
$e = $pending(1);
$check($first !== '' && ($e === null || $e->Type !== 'MZSPLITASSIGN'), "the engine accepts the corrected split answer on the flagged target ($first)");

// 2. Subcard targets: JTL_175 System Shock, "Defeat a non-leader upgrade attached to a unit." The enemy
//    Marine carries SOR_120 Academy Training, addressed as "theirGroundArena-0.u0".
$build(function ($b) {
    $b->FillResourcesForPlayer(1, 'SOR_095', 6); $b->WithCardInHandForPlayer(1, 'JTL_175');
    $b->WithGroundUnitForPlayer(2, 'SOR_095', true, 0, 0, '-');
    $b->WithGroundUnitForPlayer(1, 'SOR_095', true);
});
$u = &GetGroundArena(2); $u[0]->Subcards = [GameStateBuilder::Upgrade('SOR_120', 2)]; unset($u);
$u = &GetGroundArena(1); $u[0]->Subcards = [GameStateBuilder::Upgrade('SOR_120', 1)]; unset($u);
$act(1, 10002, 'myHand-0!FSM!');
$legal = SWUBotLegalActions($gameName, 1);
$check(($legal['decisionTooltip'] ?? '') === 'Defeat_a_non-leader_upgrade', 'fixture: the upgrade-target prompt — ' . strval($legal['decisionParam'] ?? ''));
$check(!array_filter($ids($legal), fn($c) => (bool)preg_match('/\.u\d+-\d+$/', $c)), 'no invented "…u0-N" subcard ids — ' . json_encode($ids($legal)));
$check(in_array('theirGroundArena-0.u0', $ids($legal), true) && in_array('myGroundArena-0.u0', $ids($legal), true), 'both upgrades are offered verbatim');
$act(1, 100, 'theirGroundArena-0.u0');
$e = $pending(1);
$check(($e === null || $e->Tooltip !== 'Defeat_a_non-leader_upgrade') && count(GetGroundArena(2)[0]->Subcards ?? []) === 0,
    'the engine accepts the subcard answer and defeats that upgrade');

bot_test_finish();
