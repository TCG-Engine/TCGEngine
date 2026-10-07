<?php
// A second copy of a PROTECTED card is a spare (proposal 'protecteddup', 2026-10-07 gap screen). ⚠ Needs an owner ruling before it ships:
// the current precedence ("PROTECTED (never resourced)" above "duplicates first") was owner-confirmed.
// FOUND 2026-10-07 diagnosing Vader (JTL) Yellow vs control (.claude/tmp/diag_vader): the space-wipe, Chimaera ('engine') and
// 'wipekeepaggro' tier-9 keeps run BEFORE the tier-0 duplicate check, so a second copy is never resourced — with protected duplicates in
// hand at 114 regroup picks, something else went 104 times (53 in losses). Lando s012 regroup 4: Hyperspace Disaster x2 and Chimaera held,
// Direct Hit (the single-copy answer) resourced.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_protecteddup_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(in_array('protecteddup', SWUBotProposalList(), true) && SWUBotVariantDisabled('try-protecteddup') === ['try:protecteddup'], 'protecteddup is a switchable proposal');
$LIST = [];
foreach (file(__DIR__ . '/../../Tests/BotFixtures/ash-meta-2026-09/lando-calrissian_law_blue.txt') as $l) {
    if (trim($l) === 'Sideboard') break;
    if (preg_match('/^(\d+) ([A-Z0-9]+_[A-Z0-9]+)$/', trim($l), $m) && !in_array($m[2], ['LAW_018', 'ASH_019'], true)) for ($k = 0; $k < intval($m[1]); $k++) $LIST[] = $m[2];
}
// Lando (LAW_018 on ASH_019) at regroup 4 with 5 resources and $hand, against Vader (JTL_006 on ASH_026) with four ships.
$board = function (array $hand) use ($build, $LIST) {
    $deck = $LIST;
    foreach ($hand as $c) { $k = array_search($c, $deck, true); if ($k !== false) unset($deck[$k]); }
    $build(function ($b) use ($hand, $deck) {
        $b->MyLeader('LAW_018', false); $b->MyBase('ASH_019'); $b->TheirLeader('JTL_006', false); $b->TheirBase('ASH_026');
        $b->WithCurrentRoundBeing(4); $b->FillResourcesForPlayer(1, 'SOR_095', 5);
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        foreach ($deck as $c) $b->WithCardInDeckForPlayer(1, $c);
        foreach (['JTL_085', 'LAW_135', 'JTL_217', 'JTL_T01'] as $s) $b->WithSpaceUnitForPlayer(2, $s, true);
    });
};
$pick = function (array $hand, array $variant) use ($botCtx) {
    SWUBotSetDisabledFeatures($variant);
    $r = SWUBotChooseResourceCards($botCtx('hardcontrol'), 1); SWUBotSetDisabledFeatures([]);
    return $hand[intval(substr($r[0] ?? 'myHand-99', 7))] ?? '?';
};
$H = ['SEC_078', 'SEC_078', 'ASH_052', 'JTL_078'];
$board($H);
$check($pick($H, []) === 'JTL_078', 'premise: today the single Direct Hit is resourced and both Hyperspace Disasters held; got ' . $pick($H, []));
$check($pick($H, ['try:protecteddup']) === 'SEC_078', '@try-protecteddup: the spare Hyperspace Disaster goes; got ' . $pick($H, ['try:protecteddup']));
// One copy only: it stays protected.
$H1 = ['SEC_078', 'ASH_052', 'JTL_078', 'LAW_135'];
$board($H1);
$check($pick($H1, ['try:protecteddup']) !== 'SEC_078', '@try-protecteddup: a single Hyperspace Disaster is still never resourced; got ' . $pick($H1, ['try:protecteddup']));

bot_test_finish();
