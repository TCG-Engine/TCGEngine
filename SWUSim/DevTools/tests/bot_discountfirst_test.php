<?php
// Feature 'discountfirst' (p40) — a unit whose STATIC text makes another hand card cheaper is played FIRST when that is the only order in
// which both fit this round. JTL_032 Director Krennic (unit, 2): "The first unit you play each round that has a 'When Defeated' ability
// costs 1 less." Owner's opener (Krennic Splash/Blue questionnaires, 2026-10-06): the Krennic unit + Ant Droid on round 1 — the Ant
// Droid free with the unit's discount. Autopsy (2026-10-06, 2026-10-06_autopsy_krennic-vs-aggro.md): holding both, the bot played ONLY
// the 1-drop in 28 of 28 games — Ant Droid first costs 1, so the 2-cost unit no longer fits. The 'enablerfirst' bonus reads only a When
// Played "next unit you play this phase" grant, so this static discount was invisible.
//   · lookahead: play the discounter, re-read the hand's play costs; a hand card that fits ONLY after it (and whose own play would leave
//     too little for the discounter) earns SWU_BOT_DISCOUNT_ORDER_BONUS.
// Fixtures (dictionary-checked): LAW_008 Director Krennic · ASH_019 · JTL_032 Director Krennic (unit, 2) · ASH_116 Ant Droid (1, When
//   Defeated: draw) · LOF_059 Nightsister Warrior (2, When Defeated: draw) · ASH_053 Pre Vizsla · ASH_009 · SOR_030 · LAW_038 · LAW_097
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_discountfirst_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-discountfirst') === ['discountfirst'], 'discountfirst is switchable');
$check(in_array('discountfirst', SWUBotFeatureGroups()['p40'] ?? [], true), 'discountfirst is in group p40');
$LIST = [];
foreach (file(__DIR__ . '/../../Tests/BotFixtures/ash-meta-2026-09/director-krennic_law_blue.txt') as $l) {
    if (trim($l) === 'Sideboard') break;
    if (preg_match('/^(\d+) ([A-Z0-9]+_[A-Z0-9]+)$/', trim($l), $m) && !in_array($m[2], ['LAW_008', 'ASH_019'], true)) for ($k = 0; $k < intval($m[1]); $k++) $LIST[] = $m[2];
}
// Round 1, 2 resources, $hand; the deck is the real list (a When Defeated draw is priced against the deck-out guard). The bot plays
// (the opponent passing between its actions) until it stops playing hand cards; returns the cards it played, in order.
$r1 = function (array $hand, string $variant, int $res = 2) use ($build, $act, &$gameName, $LIST) {
    $build(function ($b) use ($hand, $LIST, $res) {
        $b->MyLeader('LAW_008', false); $b->MyBase('ASH_019'); $b->TheirLeader('ASH_009', false); $b->TheirBase('SOR_030');
        $b->WithCurrentRoundBeing(1); $b->FillResourcesForPlayer(1, 'LAW_097', $res);
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        foreach ($LIST as $c) $b->WithCardInDeckForPlayer(1, $c);
        for ($k = 0; $k < 30; $k++) $b->WithCardInDeckForPlayer(2, 'LAW_038');
    });
    $played = [];
    for ($k = 0; $k < 4; $k++) {
        $l = SWUBotLegalActions($gameName, 1);
        $p = SWUBotHeuristicChoose('softcontrol', (array)$l['actions'], $l, $variant);
        $id = strval($p['cardID'] ?? '');
        if (!preg_match('/^myHand-(\d+)/', $id, $m)) break;
        $played[] = strval(GetHand(1)[intval($m[1])]->CardID ?? '?');
        $act(1, intval($p['mode'] ?? 100), $id);
        // Actions alternate: the opponent passes.
        $o = SWUBotLegalActions($gameName, 2);
        $pass = array_values(array_filter((array)($o['actions'] ?? []), fn($a) => str_contains($a['cardID'], 'Pass')))[0] ?? null;
        if ($pass !== null) $act(2, intval($pass['mode'] ?? 100), strval($pass['cardID']));
    }
    return $played;
};
// A) The opener: today the Ant Droid alone; fixed, the Krennic unit then the (free) Ant Droid.
$H = ['JTL_032', 'ASH_116', 'ASH_053'];
$check($r1($H, 'no-discountfirst') === ['ASH_116'], 'A fixture: today only the Ant Droid is played; got ' . json_encode($r1($H, 'no-discountfirst')));
$check($r1($H, '') === ['JTL_032', 'ASH_116'], 'A: the Krennic unit first, then the free Ant Droid; got ' . json_encode($r1($H, '')));
// B) The discount does not make the second card fit (Nightsister 2 → 1, with 0 left): order unchanged.
$H = ['JTL_032', 'LOF_059', 'ASH_053'];
$check($r1($H, '') === $r1($H, 'no-discountfirst'), 'B: no card fits only through the discount — unchanged; got ' . json_encode($r1($H, '')));
// C) 3 resources: either order fits both, so the order is not decided by the discount — unchanged.
$H = ['JTL_032', 'ASH_116', 'ASH_053'];
$check($r1($H, '', 3) === $r1($H, 'no-discountfirst', 3), 'C: both orders fit — unchanged; got ' . json_encode($r1($H, '', 3)) . ' vs ' . json_encode($r1($H, 'no-discountfirst', 3)));

bot_test_finish();
