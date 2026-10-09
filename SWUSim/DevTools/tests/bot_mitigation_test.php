<?php
// Feature 'mitigation' (p42) — DAMAGE MITIGATION before a planned wipe. OWNER, 2026-10-09: "if the opponent has resources to spend or actions
// to take, i can keep playing removal or ambush units to slow them down before i wipe · if i am playing as Director Krennic (LAW) leader,
// then i should still play units that benefit from getting sac'd so i can do that before i wipe · if i can put down a Sentinel to slow down
// my opponent, then do that before i wipe as well. a wipe is just meant to stabilize, but planning for a wipe should not prevent damage
// mitigation for the current round/board state. you also have to account for the fact that their leader might deploy and swing the base
// for free if you just claim initiative." — for all midrange and control decks (soft aggro too).
// 'wipeinit' (p36) held EVERY unit play into the planned wipe's arena, and its initiative claim weighed the wipe against those held plays
// (worth 0) — never against what the opponent still does for free this round. Now:
//   - a unit that MITIGATES this round is not held: a Sentinel where an enemy can still hit my base this round (a ready attacker, or their
//     leader able to deploy — it enters ready), an Ambush unit that defeats a ready enemy unit, or — with my leader's "Action [Exhaust,
//     defeat a friendly unit]" ready (LAW_008 Krennic) — fodder to sacrifice before the wipe;
//   (Removal was never held for the wipe: 'shrink-first' plays it before the wipe's initiative claim — probed 2026-10-09.)
// Fixtures (dictionary-checked): LAW_008 Director Krennic · ASH_019 · LAW_044 Single Reactor Ignition (10 for this seat: castable next round on
//   9 resources) · ASH_097 Moff Gideon (3, Sentinel) · ASH_116 Ant Droid (1, When Defeated: draw) · SEC_087 Dedra Meero (6, Ambush, 5/5) ·
//   SOR_095 Battlefield Marine (3/3, vanilla) · LOF_070 Anakin Skywalker (their 5/7) · SEC_148 Karis Nemik (their 3/2) · HMW_016 Maul (7) · JTL_020.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_mitigation_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-mitigation') === ['mitigation'], 'mitigation is switchable');
$check(in_array('mitigation', SWUBotFeatureGroups()['p42'] ?? [], true), 'mitigation is in group p42');
$pick = function (string $variant = '') use (&$gameName) {
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('softcontrol', (array)$l['actions'], $l, $variant);
    return strval($p['cardID'] ?? '');
};
$handCard = fn(string $mz) => preg_match('/^myHand-(\d+)/', $mz, $m) ? strval(GetHand(1)[intval($m[1])]->CardID ?? '') : $mz;
// Krennic (front) on 9 resources with SRI in hand (castable next round) + $card; their $theirs on the ground (ready?), Maul with $oppRes.
$board = function (string $card, array $theirs, bool $ready = true, int $oppRes = 3, bool $leaderReady = true) use ($build) {
    $build(function ($b) use ($card, $theirs, $ready, $oppRes, $leaderReady) {
        $b->MyLeader('LAW_008', $leaderReady); $b->MyBase('ASH_019', 6); $b->FillResourcesForPlayer(1, 'SOR_095', 9); $b->WithCurrentRoundBeing(8);
        $b->TheirLeader('HMW_016'); $b->TheirBase('JTL_020', 6); $b->FillResourcesForPlayer(2, 'SOR_095', $oppRes);
        foreach ($theirs as $t) $b->WithGroundUnitForPlayer(2, $t, $ready);
        $b->WithCardInHandForPlayer(1, 'LAW_044'); $b->WithCardInHandForPlayer(1, $card);
        for ($k = 0; $k < 20; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
    });
};
$fixture = fn() => _SWUBotWipeNextRound(1);

// A) Sentinel vs a ready Anakin that can hit my base: today Moff Gideon is held for the wipe; now he goes down.
$board('ASH_097', ['LOF_070']);
$check(($fixture()['cid'] ?? '') === 'LAW_044', 'A fixture: SRI is the wipe planned for next round');
$check($handCard($pick('no-mitigation')) !== 'ASH_097', 'A fixture: today Gideon is held; got ' . $handCard($pick('no-mitigation')));
$check($handCard($pick()) === 'ASH_097', 'A: the Sentinel goes down before the wipe; got ' . $handCard($pick()));
// A2) Nothing of theirs can attack this round (Anakin exhausted, Maul cannot deploy on 3): Gideon is still held — unchanged. (He is no
//     Krennic fodder either: a Sentinel is never the sacrifice.)
$board('ASH_097', ['LOF_070'], false);
$check($pick() === $pick('no-mitigation'), 'A2: no damage to mitigate this round — unchanged; got ' . json_encode([$pick(), $pick('no-mitigation')]));
$check($handCard($pick()) !== 'ASH_097', 'A2: Gideon still held');
// A3) …but their Maul CAN deploy (7 resources; his unit enters ready): the Sentinel goes down.
$board('ASH_097', ['LOF_070'], false, 7);
$check($handCard($pick()) === 'ASH_097', 'A3: their leader can deploy and swing — the Sentinel goes down; got ' . $handCard($pick()));

// B) Krennic's Action ready: the Ant Droid (When Defeated: draw) is fodder to sacrifice before the wipe — played.
$board('ASH_116', ['LOF_070'], false);
$check($handCard($pick('no-mitigation')) !== 'ASH_116', 'B fixture: today the Ant Droid is held; got ' . $handCard($pick('no-mitigation')));
$check($handCard($pick()) === 'ASH_116', 'B: fodder for Krennic goes down before the wipe; got ' . $handCard($pick()));
// B2) Krennic exhausted (his Action spent this round): no sacrifice before the wipe — held as before.
$board('ASH_116', ['LOF_070'], false, 3, false);
$check($pick() === $pick('no-mitigation'), 'B2: no Action to sacrifice into — unchanged; got ' . json_encode([$pick(), $pick('no-mitigation')]));

// C) Dedra Meero (Ambush 5/5) can defeat their ready Karis (3/2): played to slow them down.
$board('SEC_087', ['SEC_148']);
$check($handCard($pick('no-mitigation')) !== 'SEC_087', 'C fixture: today Dedra is held; got ' . $handCard($pick('no-mitigation')));
$check($handCard($pick()) === 'SEC_087', 'C: the Ambush kill goes before the wipe; got ' . $handCard($pick()));

// C2) …but into a ready Anakin (5/7) her Ambush kills nothing: held for the wipe, as before.
$board('SEC_087', ['LOF_070']);
$check($handCard($pick()) !== 'SEC_087', 'C2: an Ambush that defeats nothing is still held; got ' . $handCard($pick()));

// D) A plain body (a 3/3 Marine) mitigates nothing: still held for the wipe, as before.
$board('SOR_095', ['LOF_070']);
$check($handCard($pick()) !== 'SOR_095', 'D: a vanilla unit is still held for the wipe; got ' . $handCard($pick()));

bot_test_finish();
