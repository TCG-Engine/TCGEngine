<?php
// Resourcing against SPACE aggro: a ground Sentinel is a wall only while the enemy is on the ground (proposal 'wallkeeparena',
// 2026-10-07 gap screen). ⚠ Needs an owner ruling before it ships: does "never resource a Sentinel vs aggro" (Krennic Blue, 2026-10-06)
// cover a Sentinel in an arena the opponent is not in?
// FOUND 2026-10-07 diagnosing Vader (JTL) Yellow vs control (.claude/tmp/diag_vader): 'wallkeep' (p39) puts every Sentinel in the keep
// tier, so against a SPACE-only Vader a single-copy space answer went to resources instead — 170 of 1,433 traced picks (Mando 72,
// Aurra 66, Lando 32). Aurra s012 regroup 4: resourced Pirate Snub Fighter, kept Moff Gideon.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_wallkeeparena_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(in_array('wallkeeparena', SWUBotProposalList(), true) && SWUBotVariantDisabled('try-wallkeeparena') === ['try:wallkeeparena'], 'wallkeeparena is a switchable proposal');
$LIST = [];
foreach (file(__DIR__ . '/../../Tests/BotFixtures/ash-meta-2026-09/aurra-sing_law_data-vault.txt') as $l) {
    if (trim($l) === 'Sideboard') break;
    if (preg_match('/^(\d+) ([A-Z0-9]+_[A-Z0-9]+)$/', trim($l), $m) && !in_array($m[2], ['LAW_004', 'JTL_024'], true)) for ($k = 0; $k < intval($m[1]); $k++) $LIST[] = $m[2];
}
// Aurra Sing DV (LAW_004 on JTL_024) at regroup $rnd with $rnd+1 resources and $hand; their $leader with $their units.
$board = function (array $hand, int $rnd, string $leader, string $base, callable $their) use ($build, $LIST) {
    $deck = $LIST;
    foreach ($hand as $c) { $k = array_search($c, $deck, true); if ($k !== false) unset($deck[$k]); }
    $build(function ($b) use ($hand, $rnd, $deck, $their, $leader, $base) {
        $b->MyLeader('LAW_004', false); $b->MyBase('JTL_024'); $b->TheirLeader($leader, false); $b->TheirBase($base);
        $b->WithCurrentRoundBeing($rnd); $b->FillResourcesForPlayer(1, 'SOR_095', $rnd + 1);
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
// The s012 shape: Moff Gideon (ASH_097, a ground Sentinel), Pirate Snub Fighter, Eager Escort Fighter, Out the Airlock — Vader with four ships.
$H = ['ASH_097', 'LAW_135', 'JTL_112', 'JTL_079'];
$ships = function ($b) { foreach (['JTL_085', 'LAW_135', 'JTL_217', 'JTL_T01'] as $s) $b->WithSpaceUnitForPlayer(2, $s, true); };
$board($H, 4, 'JTL_006', 'ASH_026', $ships);
$check($pick($H, []) !== 'ASH_097', 'premise: today the ground Sentinel is kept and something else is resourced; got ' . $pick($H, []));
$check($pick($H, ['try:wallkeeparena']) === 'ASH_097', '@try-wallkeeparena: the off-arena Sentinel is resourced; got ' . $pick($H, ['try:wallkeeparena']));
// With a Vader ground unit out, the ground Sentinel is a wall again and stays.
$board($H, 4, 'JTL_006', 'ASH_026', function ($b) use ($ships) { $ships($b); $b->WithGroundUnitForPlayer(2, 'SOR_095', true); });
$check($pick($H, ['try:wallkeeparena']) !== 'ASH_097', '@try-wallkeeparena: an enemy ground unit keeps the ground wall; got ' . $pick($H, ['try:wallkeeparena']));
// Against GROUND aggro (Ahsoka Blue, not space-flavoured) with no unit out, the wall stays: the lever reads the space matchup only.
$board($H, 4, 'ASH_009', 'ASH_019', fn($b) => null);
$check($pick($H, ['try:wallkeeparena']) === $pick($H, []), '@try-wallkeeparena: vs ground aggro the pick is unchanged; got ' . $pick($H, ['try:wallkeeparena']));

bot_test_finish();
