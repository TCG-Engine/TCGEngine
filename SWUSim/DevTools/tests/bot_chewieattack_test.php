<?php
// Feature 'chewieattack' (p42) — LAW_013 Chewbacca, Hero of Kessel, deployed: "On Attack: You may defeat a friendly resource. If you do,
// deal 2 damage to a unit and create a Credit token." OWNER RULING 2026-10-08: "it should say yes to kill something, soften it up for Red
// Five, or break a shield on a sentinel". Leader audit: accepted 98.6% (3,657 of 3,709) only because nothing priced the prompt — the
// first-listed resource won over PASS.
// Fixed: a resource is offered only when the 2 damage on some enemy unit (a) defeats it, (b) leaves it in reach of a ready friendly
// "On Attack: You may deal N damage to a damaged unit" (JTL_151 Red Five: 2), or (c) pops the Shield of an enemy Sentinel; otherwise PASS.
// (WHICH resource does not matter — CR rearrangement, see resource_defeat_keeps_ready_test.)
// Fixtures (dictionary-checked): LAW_013 Chewbacca (deployed 5/6) · LAW_019 Alliance Outpost · SEC_148 Karis Nemik (3/2) · LAW_045 Zeb
//   Orrelios (4/4 Sentinel) · ASH_009 Ahsoka Tano (an aggro leader) · LOF_061 Secretive Sage (2/2) · SOR_T02 Shield token · JTL_151 Red Five · SOR_095.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_chewieattack_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-chewieattack') === ['chewieattack'], 'chewieattack is switchable');
$check(in_array('chewieattack', SWUBotFeatureGroups()['p42'] ?? [], true), 'chewieattack is in group p42');
$pick = function (string $variant = '') use (&$gameName) {
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('hyperaggro', (array)$l['actions'], $l, $variant);
    return strval($p['cardID'] ?? '');
};
$nm = fn(string $mz) => strval((@GetZoneObject($mz))->CardID ?? '');
// Deployed Chewbacca attacks; $theirs on their ground; $red: my ready Red Five in space. Steps to the On Attack's resource prompt.
$toPrompt = function (array $theirs, bool $red = false, string $target = '', bool $redReady = true, array $shielded = [], string $lead = '') use ($build, $act, $botCtx) {
    $build(function ($b) use ($theirs, $red, $redReady, $shielded, $lead) {
        if ($lead !== '') $b->TheirLeader($lead);
        $b->MyLeader('LAW_013', true, true, true, 'unit'); $b->MyBase('LAW_019'); $b->WithCurrentRoundBeing(4);
        $b->FillResourcesForPlayer(1, 'SOR_095', 4);
        if ($red) $b->WithSpaceUnitForPlayer(1, 'JTL_151', $redReady);
        foreach ($theirs as $t) $b->WithGroundUnitForPlayer(2, $t, false);
        foreach ($shielded as $si) $b->WithUpgradesOnGroundUnitForPlayer(2, $si, [GameStateBuilder::Upgrade('SOR_T02', 2)]);   // a Shield token
        for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
    });
    $act(1, 10002, 'myGroundArena-0!FSM!');
    for ($k = 0; $k < 3 && strval($botCtx('hyperaggro')['tooltip'] ?? '') !== 'Choose_a_resource'; $k++) {
        $ids = array_map(fn($a) => strval($a['cardID']), $botCtx('hyperaggro')['actions']);
        $act(1, 100, $target !== '' && in_array($target, $ids, true) ? $target : (in_array('theirBase-0', $ids, true) ? 'theirBase-0' : $ids[0]));
    }
    return strval($botCtx('hyperaggro')['tooltip'] ?? '');
};
$accepts = fn(string $p) => str_starts_with($p, 'myResources-');

// A) A Karis (3/2) to kill: yes — and the 2 goes on Karis.
$check($toPrompt(['SEC_148']) === 'Choose_a_resource', 'A fixture: the On Attack resource prompt');
$check($accepts($pick()), 'A: the 2 kills Karis — defeat a resource; got ' . $pick());
$act(1, 100, $pick());
$t = $pick();
$check($nm($t) === 'SEC_148', 'A: the 2 on Karis; got ' . $t);

// B) Only an undamaged 4/4 Zeb (Sentinel, attacked) and no Red Five: nothing to kill or set up — no. Today: yes.
$check($toPrompt(['LAW_045'], false, 'theirGroundArena-0') === 'Choose_a_resource', 'B fixture: the prompt');
$check($accepts($pick('no-chewieattack')), 'B fixture: today it accepts anyway; got ' . $pick('no-chewieattack'));
$check($pick() === 'PASS', 'B: no kill, no Red Five, no Shield — decline; got ' . $pick());

// C) …with my Red Five ready: 2 now + Red Five's 2 on a damaged unit kill a 4/4 — yes.
$toPrompt(['LAW_045'], true, 'theirGroundArena-0');
$check($accepts($pick()), 'C: soften for Red Five — defeat a resource; got ' . $pick());

// D) A Shielded Sentinel (Zeb 4/4 with a Shield token) blocks: nothing dies to the 2, but it pops the Shield — yes.
$toPrompt(['LAW_045'], false, 'theirGroundArena-0', true, [0]);
$check($accepts($pick()), 'D: break the Sentinel\'s Shield — defeat a resource; got ' . $pick());

// E) A Shield on a unit that is NOT a Sentinel just eats the 2 (Secretive Sage 2/2 with a Shield token — no kill through it): no.
$toPrompt(['LOF_061'], false, '', true, [0]);
$check($pick() === 'PASS', 'E: a non-Sentinel Shield eats the 2 — decline; got ' . $pick());

// F) Red Five EXHAUSTED (it cannot attack this round): no finish to set up — no.
$toPrompt(['LAW_045'], true, 'theirGroundArena-0', false);
$check($pick() === 'PASS', 'F: an exhausted Red Five finishes nothing this round — decline; got ' . $pick());

// G) OWNER RULING 2026-10-09 (after the confirmation sweep: the restricted rule lost 4 / 20 paired games vs aggro leaders, p=.0015, and was
//    neutral otherwise): "Vs aggro: always yes" — against an aggro leader (SWU_BOT_AGGRO_LEADERS; ASH_009 Ahsoka) the race wants the 2
//    damage and the Credit whenever an enemy unit is there to hit. Case B's board (only an undamaged Zeb, no Red Five), vs Ahsoka: yes.
$toPrompt(['LAW_045'], false, 'theirGroundArena-0', true, [], 'ASH_009');
$check(str_starts_with($pick(), 'myResources-'), 'G: vs an aggro leader — always yes; got ' . $pick());
// G2) …the default opponent (SOR_010) is not an aggro leader: case B's decline stands (pinned above).

bot_test_finish();
