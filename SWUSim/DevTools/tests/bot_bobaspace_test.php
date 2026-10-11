<?php
// Krennic vs Boba Fett (JTL) Blue resourcing, two proposals from the 2026-10-07 gap screen (Krennic Blue 18% vs Boba Blue, real 42.9%;
// Splash 7%, real 24%). Diagnosis (.claude/tmp/diag_boba): the Krennic bots lose the SPACE arena — Boba's ships hit the base unanswered.
//   bobaspace     — Boba Fett (JTL_009) is a SPACE deck (owner: "space midrange, 27 of 41 units space"), but his flavour was only
//                   'burn', so the resourcing tiers read "space aggro" off the BOARD (2+ ships). With a ground Marrok out, Hyperspace
//                   Disaster looked irrelevant and went to resources: 14 games, all losses (boba-blue.krennic-blue s031 R4 resourced HSD
//                   and kept Ravager, against the owner's "resource Ravager / late bombs"). Adds 'space' to his flavours.
//   cravinganswer — Craving Power (LOF_091) is the deck's only spot answer that reaches a piloted ship (owner lists it as removal),
//                   but it is tagged power-strike / damage-enemy-unit, not removal, so the tiers' $answer missed it: resourced 34 times
//                   in 30 of 82 Blue losses. A power-strike that damages an enemy unit counts as an answer when RESOURCING.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_bobaspace_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
foreach (['bobaspace', 'cravinganswer'] as $p)
    $check(in_array($p, SWUBotProposalList(), true) && SWUBotVariantDisabled("try-$p") === ["try:$p"], "$p is a switchable proposal");
$LIST = [];
foreach (file(__DIR__ . '/../../Tests/BotFixtures/ash-meta-2026-09/director-krennic_law_blue.txt') as $l) {
    if (trim($l) === 'Sideboard') break;
    if (preg_match('/^(\d+) ([A-Z0-9]+_[A-Z0-9]+)$/', trim($l), $m) && !in_array($m[2], ['LAW_008', 'ASH_019'], true)) for ($k = 0; $k < intval($m[1]); $k++) $LIST[] = $m[2];
}
// Krennic Blue (LAW_008 on ASH_019) in round $rnd with $rnd+1 resources and $hand; Boba Fett (JTL_009 on ASH_019) with $their units.
$board = function (array $hand, int $rnd, callable $their) use ($build, $LIST) {
    $deck = $LIST;
    foreach ($hand as $c) { $k = array_search($c, $deck, true); if ($k !== false) unset($deck[$k]); }
    $build(function ($b) use ($hand, $rnd, $deck, $their) {
        $b->MyLeader('LAW_008', false); $b->MyBase('ASH_019'); $b->TheirLeader('JTL_009', false); $b->TheirBase('ASH_019');
        $b->WithCurrentRoundBeing($rnd); $b->FillResourcesForPlayer(1, 'LAW_097', $rnd + 1);
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        foreach ($deck as $c) $b->WithCardInDeckForPlayer(1, $c);
        $their($b);
    });
};
$pick = function (array $hand, array $variant) use ($botCtx) {
    SWUBotSetDisabledFeatures($variant);
    $r = SWUBotChooseResourceCards($botCtx('softcontrol'), 1); SWUBotSetDisabledFeatures([]);
    return $hand[intval(substr($r[0] ?? 'myHand-99', 7))] ?? '?';
};

// bobaspace — the s031 R4 shape: Koska, No Glory, Chimaera, Hyperspace Disaster, Ravager in hand; Boba has a ground Marrok and one ship.
$H = ['ASH_079', 'JTL_043', 'ASH_052', 'SEC_078', 'ASH_102'];
$their = function ($b) { $b->WithGroundUnitForPlayer(2, 'ASH_030', true); $b->WithSpaceUnitForPlayer(2, 'JTL_162', true); };
$board($H, 4, $their);
$check(!in_array('space', SWUBotDeckFlavours(2), true), 'premise: today Boba Fett has no space flavour');
$check($pick($H, []) === 'SEC_078', 'premise: today Hyperspace Disaster is resourced against Boba; got ' . $pick($H, []));
$check(in_array('space', (function () { SWUBotSetDisabledFeatures(['try:bobaspace']); $f = SWUBotDeckFlavours(2); SWUBotSetDisabledFeatures([]); return $f; })(), true),
    '@try-bobaspace: Boba Fett reads as a space deck');
$check($pick($H, ['try:bobaspace']) !== 'SEC_078', '@try-bobaspace: Hyperspace Disaster stays in hand; got ' . $pick($H, ['try:bobaspace']));
// Another leader is untouched: Ahsoka Yellow keeps her own flavours.
$build(function ($b) { $b->TheirLeader('ASH_009', false); $b->TheirBase('SOR_030'); });
SWUBotSetDisabledFeatures(['try:bobaspace']); $f = SWUBotDeckFlavours(2); SWUBotSetDisabledFeatures([]);
$check($f === ['mixed-space'], '@try-bobaspace: other leaders keep their flavours; got ' . json_encode($f));

// cravinganswer — Blue R2: Craving Power next to two cheap plays and a 7+ bomb; Boba has one ship.
$C = ['LOF_091', 'LAW_097', 'ASH_116', 'ASH_048', 'JTL_043'];
$their1 = function ($b) { $b->WithSpaceUnitForPlayer(2, 'JTL_237', true); };
$board($C, 2, $their1);
$check($pick($C, []) === 'LOF_091', 'premise: today Craving Power is resourced; got ' . $pick($C, []));
$check($pick($C, ['try:cravinganswer']) !== 'LOF_091', '@try-cravinganswer: Craving Power stays as an answer; got ' . $pick($C, ['try:cravinganswer']));

bot_test_finish();
