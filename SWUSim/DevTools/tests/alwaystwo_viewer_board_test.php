<?php
// 1v1 Always Two — a seat's page is TWO board views (SWUSim/AlwaysTwoTable.php), and since the 2×2 table (spec "The 2×2 table",
// 2026-11-20) the RIGHT one — board seat+2 — carries the log. So a request viewed AS BOARD 3 is still seat 1 looking: it reads the
// private log lines of boards 1 AND 3 and controls both. Before the 2×2 table only the seat's own board number (1/2) ever carried the
// log, so the visibility helpers took the viewer as a seat — a board-3 viewer was read as "seat 3" and lost board 1's private lines.
// Control: Team Suns keeps one board per seat.
//   docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 SWUSim/DevTools/tests/alwaystwo_viewer_board_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';

$four = function (bool $shared) use ($build) {
    return $build(function ($b) use ($shared) {
        $b->WithSeatOrder('1234'); $b->WithLiveSeats('1234');
        $b->WithGlobalEffectForPlayer(1, 'SWU_MODE_TEAMS');
        if ($shared) { $b->WithGlobalEffectForPlayer(1, 'SWU_MODE_ALWAYSTWO'); $b->WithGlobalEffectForPlayer(1, 'SWU_ALWAYSTWO_SHARED_SEAT'); }
    });
};
// One private line per board plus a public one, in the stored log format (type|visibility|text).
$log = implode('<NL>', ['INFO|P1|board one private', 'INFO|P2|board two private', 'INFO|P3|board three private',
                        'INFO|P4|board four private', 'INFO|ALL|public line']);
$texts = fn(array $rows) => array_map(fn($e) => explode('|', $e, 3)[2], $rows);

echo "ViewerIsBoardThree:\n";
$four(true);
$seen = $texts(SWUFilterGameLogForViewer($log, 3, false));
$check($seen === ['board one private', 'board three private', 'public line'], 'board 3\'s view (seat 1) reads boards 1+3 private lines; got ' . json_encode($seen));
$seen = $texts(SWUFilterGameLogForViewer($log, 4, false));
$check($seen === ['board two private', 'board four private', 'public line'], 'board 4\'s view (seat 2) reads boards 2+4 private lines; got ' . json_encode($seen));
$check(SWUViewerControlsBoard(3, 1) && SWUViewerControlsBoard(3, 3) && !SWUViewerControlsBoard(3, 2) && !SWUViewerControlsBoard(3, 4),
    'board 3\'s view controls boards 1 and 3, not 2 or 4');
$check(SWUViewerControlsBoard(4, 2) && !SWUViewerControlsBoard(4, 1), 'board 4\'s view controls board 2, not board 1');
$seen = $texts(SWUFilterGameLogForViewer($log, 1, false));
$check($seen === ['board one private', 'board three private', 'public line'], 'board 1\'s view unchanged; got ' . json_encode($seen));

echo "ControlTeamSuns:\n";
$four(false);
$seen = $texts(SWUFilterGameLogForViewer($log, 3, false));
$check($seen === ['board three private', 'public line'], 'Team Suns: seat 3 reads only its own private lines; got ' . json_encode($seen));
$check(!SWUViewerControlsBoard(3, 1), 'Team Suns: seat 3 does not control board 1');

$f = $GLOBALS['BOT_TEST_FAILS'];
echo $f === 0 ? "ALL PASS\n" : "$f FAILED\n";
exit($f === 0 ? 0 : 1);
