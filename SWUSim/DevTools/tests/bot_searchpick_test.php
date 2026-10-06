<?php
// Feature 'searchpick' (p36) — Admiral Ackbar's flip ("You may defeat this unit. If you do, search the top 10 cards of your deck for
// any number of space units with combined cost 5 or less and play each of them for free"). Ninin (Ahsoka Yellow) vs Reprint_Cad,
// human game 2026-10-04, R4: four ships (Open Circle Ace x2, A-Wing, Jedi Interceptor) for 5. Owner: build it. Trace mining of 125
// bot Ackbar searches (Ahsoka Yellow fixture): 51 took FEWER ships than the best legal pick, 17 broke the uniqueness rule.
// Two causes:
//   (1) DevTools/TestAutomationBridge.php emitted search picks smallest-first under a 40-candidate cap — 10 singles + 45 pairs
//       already exceed it, so a 3-4 ship pick was never OFFERED. Fixed (ungated: strictly more choice): the MAXIMAL picks the cap
//       cut off are appended, biggest first.
//   (2) _SWUBotSearchScore summed card values blind to uniqueness: a second copy of a unique, or one already in play, is defeated
//       at once. Under 'searchpick' such a card scores -1.
// Fixtures (dictionary-checked): ASH_110 Admiral Ackbar (5) · ASH_009 Ahsoka Tano · ASH_201 Open Circle Ace (1) · SEC_213 A-Wing (1)
//   · JTL_212 Republic Y-Wing (1) · HMW_256 Jedi Interceptor (2) · SEC_215 Emissary's Sheathipede (2) · ASH_203 Mando's N-1
//   Starfighter (2, unique) · JTL_096 Blue Leader (3, unique) · JTL_249 Millennium Falcon (3, unique) · SEC_099 Naboo Royal
//   Starship (4, unique) · LAW_097 (resources)
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_searchpick_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-searchpick') === ['searchpick'], 'searchpick is switchable');
$check(in_array('searchpick', SWUBotFeatureGroups()['p36'] ?? [], true), 'searchpick is in group p36');
$TEN = ['JTL_096', 'ASH_203', 'ASH_203', 'SEC_099', 'JTL_249', 'SEC_213', 'JTL_212', 'ASH_201', 'HMW_256', 'SEC_215'];
// Seat 1 plays Ackbar, says YES to the flip, and stops at the search prompt. $mine = space units already in play.
$toSearch = function (array $mine = []) use ($build, $act, $TEN, &$gameName) {
    $build(function ($b) use ($TEN, $mine) {
        $b->MyLeader('ASH_009', false); $b->FillResourcesForPlayer(1, 'LAW_097', 9);
        $b->WithCardInHandForPlayer(1, 'ASH_110');
        foreach ($TEN as $c) $b->WithCardInDeckForPlayer(1, $c);
        foreach ($mine as $c) $b->WithSpaceUnitForPlayer(1, $c, false);
    });
    $act(1, 10002, 'myHand-0!FSM!');
    for ($i = 0; $i < 3; $i++) {
        $l = SWUBotLegalActions($gameName, 1);
        if (($l['decisionType'] ?? '') === 'TOPDECKSEARCH') return $l;
        if (($l['decisionType'] ?? '') === 'YESNO') { $act(1, 100, 'YES'); continue; }
        break;
    }
    return SWUBotLegalActions($gameName, 1);
};
$pickOf = function (array $l, string $variant) {
    $p = SWUBotHeuristicChoose('midrange', (array)$l['actions'], $l, $variant);
    return array_values(array_filter(explode(',', strval($p['cardID'] ?? ''))));
};

// A) The enumerator now OFFERS a four-ship pick (combined cost 5).
$l = $toSearch();
$check(($l['decisionType'] ?? '') === 'TOPDECKSEARCH', 'A fixture: Ackbar\'s flip reaches the search; got ' . var_export($l['decisionType'] ?? null, true));
$sizes = array_map(fn($a) => count(array_filter(explode(',', strval($a['cardID'])))), (array)$l['actions']);
$check(max($sizes) >= 4, 'A: a four-ship pick is offered; largest offered = ' . max($sizes));
// E) The picks appended past the 40-candidate cap come biggest first, starting at the largest legal size (4 ships for 5).
$tail = array_slice((array)$l['actions'], BridgeTopDeckSearchActionCap(), -1);   // past the cap, before the trailing "take nothing"
$sizesTail = array_map(fn($a) => count(array_filter(explode(',', strval($a['cardID'])))), $tail);
$sortedDesc = $sizesTail; rsort($sortedDesc);
$check(count($tail) > 0 && $sizesTail === $sortedDesc && $sizesTail[0] === 4, 'E: appended picks are biggest first, from 4; got ' . json_encode($sizesTail));
// B) The bot takes four ships.
$pick = $pickOf($l, '');
$check(count($pick) === 4, 'B: four ships for 5; got ' . json_encode($pick));
// C) Mando's N-1 Starfighter (unique) already in play: the pick never takes another copy (it would be defeated at once).
$l = $toSearch(['ASH_203']);
$GLOBALS['SWUBotPinnedDisabled'] = SWU_BOT_PART38_FEATURES;   // the pre-searchpick baseline, isolated from curve value (p38, 2026-10-06)
$pickOff = $pickOf($l, 'no-searchpick');
unset($GLOBALS['SWUBotPinnedDisabled']);
$pickOn = $pickOf($l, '');
$check(!in_array('ASH_203', $pickOn, true), 'C: no second N-1 Starfighter; got ' . json_encode($pickOn));
$check(in_array('ASH_203', $pickOff, true), 'C fixture: today the scorer takes it; got ' . json_encode($pickOff));
// D) …nor two copies of a unique in one pick (none in play).
$toSearch();
$s1 = _SWUBotSearchScore(1, 'ASH_203,SEC_213', SWUBotWeights('midrange', 1));
$GLOBALS['SWUBotDisabledFeatures'] = []; $s2 = _SWUBotSearchScore(1, 'ASH_203,ASH_203', SWUBotWeights('midrange', 1));
$check($s2 < $s1, "D: two N-1 Starfighters score below one + an A-Wing; got $s2 vs $s1");

bot_test_finish();
