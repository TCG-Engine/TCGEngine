<?php
// Feature 'searchvalue' (p42) — "Search the top N cards of your deck for …" effects. Their tag ('search-top-deck', 58 cards) had NO weight,
// so the search was worth 0 — e.g. LOF_057 Owen Lars ("When Defeated: Search the top 5 cards of your deck for a Force unit … draw it") read
// as an effect-less body, and HMW_016 Maul never chose him though the audit counted him an ideal pick. OWNER RULINGS 2026-10-08:
//   - to hand ("… and draw it/them", "… into your hand"): a draw per card it can find ("up to 2" = 2; "any number of" = 2);
//   - into play (LOF_100 Kelleran Beq "play it. It costs 3 resources less", SOR_104 U-Wing Reinforcement "up to 3 units with combined cost 7
//     or less … for free"): a draw per card played PLUS the resources it saves (W['develop'] a resource, as a play's cost is priced);
//   - any other (discard it, put it on top, resource it): half a draw.
// A draw is W['draw'] × SWUBotDrawMultiplier (the deck-out guard), as every draw tag is priced.
// Fixtures (dictionary-checked): LOF_057 Owen Lars · SOR_084 Grand Moff Tarkin (up to 2) · LOF_100 Kelleran Beq · SOR_104 U-Wing
//   Reinforcement · LOF_103 Following the Path (top of deck) · SOR_095.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_searchvalue_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-searchvalue') === ['searchvalue'], 'searchvalue is switchable');
$check(in_array('searchvalue', SWUBotFeatureGroups()['p42'] ?? [], true), 'searchvalue is in group p42');
$build(function ($b) {
    $b->MyLeader('HMW_016', true); $b->MyBase('JTL_020');
    for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
});
$W = SWUBotWeights('midrange', 1);
$draw = $W['draw'] * SWUBotDrawMultiplier(1);
$gain = function (string $cid) use ($W) {
    $on = _SWUBotPlayValue(1, $cid, $W); SWUBotSetDisabledFeatures(['searchvalue']); $off = _SWUBotPlayValue(1, $cid, $W); SWUBotSetDisabledFeatures([]);
    return $on - $off;
};
$near = fn(float $a, float $b) => abs($a - $b) < 1e-6;
$check($draw > 0, 'fixture: a draw is worth something here (deck of 30); got ' . $draw);
$check($near($gain('LOF_057'), $draw), 'Owen Lars (a Force unit, to hand): one draw; got ' . json_encode([$gain('LOF_057'), $draw]));
$check($near($gain('SOR_084'), 2 * $draw), 'Tarkin (up to 2, to hand): two draws; got ' . json_encode([$gain('SOR_084'), 2 * $draw]));
$check($near($gain('LOF_100'), $draw + 3 * $W['develop']), 'Kelleran (play it, 3 less): a draw + 3 resources; got ' . json_encode([$gain('LOF_100'), $draw + 3 * $W['develop']]));
$check($near($gain('SOR_104'), 3 * $draw + 7 * $W['develop']), 'U-Wing (up to 3, combined 7, free): three draws + 7 resources; got ' . json_encode([$gain('SOR_104'), 3 * $draw + 7 * $W['develop']]));
$check($near($gain('LOF_103'), 0.5 * $draw), 'Following the Path (put on top): half a draw; got ' . json_encode([$gain('LOF_103'), 0.5 * $draw]));
$check($near($gain('SOR_095'), 0.0), 'a card with no search: unchanged');
// …and Maul (HMW_016) now sees Owen Lars's When Defeated: his effects are worth a draw (were 0 — "no effect").
$e = _SWUBotEffectOnlyValue(1, 'LOF_057', $W);
SWUBotSetDisabledFeatures(['searchvalue']); $e0 = _SWUBotEffectOnlyValue(1, 'LOF_057', $W); SWUBotSetDisabledFeatures([]);
$check($e !== null && $e0 !== null && $e > $e0 + 0.05, 'Maul: Owen Lars\'s When Defeated search is an effect worth playing for; got ' . json_encode([$e, $e0]));

bot_test_finish();
