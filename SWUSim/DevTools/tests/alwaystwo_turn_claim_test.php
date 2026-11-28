<?php
// Always Two — SWUClaimTeamTurn only moves the turn to a board that may take it (final review finding, 2026-11-20). Some input paths
// claim without a turn gate in front (PlayFromDiscard / PlayFromOpponentDiscard), so the claim itself must refuse an ENEMY board:
// otherwise a stale or crafted request hands the other team the turn.
//   docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 SWUSim/DevTools/tests/alwaystwo_turn_claim_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
$four = fn() => $build(function ($b) {
    $b->WithSeatOrder('1234'); $b->WithLiveSeats('1234');
    foreach (['SWU_MODE_TEAMS', 'SWU_MODE_ALWAYSTWO', 'SWU_ALWAYSTWO_SHARED_SEAT'] as $f) $b->WithGlobalEffectForPlayer(1, $f);
});
$four();
SWUClaimTeamTurn(2);
$check(intval(GetTurnPlayer()) === 1, 'an ENEMY board cannot claim Red\'s turn; turn player ' . GetTurnPlayer());
SWUClaimTeamTurn(3);
$check(intval(GetTurnPlayer()) === 3, 'the teammate board can; turn player ' . GetTurnPlayer());
$f = $GLOBALS['BOT_TEST_FAILS'];
echo $f === 0 ? "ALL PASS\n" : "$f FAILED\n";
exit($f === 0 ? 0 : 1);
