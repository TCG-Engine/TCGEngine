<?php
// Drawn games and drawn matches in the shared match layer (docs/superpowers/specs/2026-10-30-swusim-draws-design.md).
// CR 6.3: both bases at 0 at the same time = a draw. A drawn game is a decided game with no winner; a drawn MATCH is one
// that ran its full length with equal game wins. The game after a draw picks first player at random (no forced seat).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off DevTools/tdd-regression/test_match_draws.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', '1');
include_once __DIR__ . '/../../Core/Match/MatchFlow.php';
include_once __DIR__ . '/../../Core/DecisionQueueController.php';
include_once __DIR__ . '/../../Core/MatchReplay.php';

// The decision-queue variable store a sim's GamestateParser normally provides.
$GLOBALS['tmdVars'] = '';
if (!function_exists('GetDecisionQueueVariables')) { function GetDecisionQueueVariables() { return $GLOBALS['tmdVars']; } }
if (!function_exists('SetDecisionQueueVariables')) { function SetDecisionQueueVariables($v) { $GLOBALS['tmdVars'] = $v; } }

$fails = 0;
function check($cond, $msg) { global $fails; echo ($cond ? '  ok: ' : '  BAD: ') . $msg . "\n"; if (!$cond) $fails++; }

const R = 'MatchDrawTestSim';
$setupOpts = [];      // every setupGame call's opts, in order
$deckStats = [];      // recordDeckStats calls: winner seat per call
MatchRegisterHooks(R, [
    'resolveLobbyDecks' => fn($lobby) => [1 => ['originalDeck' => ['A'], 'authKey' => 'k1'], 2 => ['originalDeck' => ['B'], 'authKey' => 'k2']],
    'validateDeck'      => fn($d, $f) => true,
    'setupGame'         => function ($lobby, $opts) use (&$setupOpts) {
        $setupOpts[] = $opts;
        $g = 'MDTgame' . count($setupOpts) . '_' . getmypid();
        @mkdir(MatchRepoRoot() . '/' . R . '/Games/' . $g, 0777, true);
        return $g;
    },
    'recordDeckStats'   => function (array &$m, $seat) use (&$deckStats) { $deckStats[] = intval($seat); },
    'captureGameDetail' => fn() => ['turns' => 5],
]);

$players = [1 => ['originalDeck' => ['A'], 'authKey' => 'k1'], 2 => ['originalDeck' => ['B'], 'authKey' => 'k2']];
$newMatch = function (string $queueType) use ($players): string { return MatchCreate(R, 'premier', $queueType, $players, true); };
$addGame = function (string $mid, string $g): void {
    MatchWithLock(R, $mid, function (&$m) use ($g) { $m['games'][] = ['gameName' => $g, 'gameNumber' => count($m['games']) + 1, 'winner' => null]; });
    @mkdir(MatchRepoRoot() . '/' . R . '/Games/' . $g, 0777, true);
    MatchWriteRef(R, $g, $mid, 1);
};
$play = function (string $mid, array $results) use ($addGame): array {   // results: 1, 2 or 'D'
    foreach ($results as $i => $r) {
        $g = "g{$i}_" . $mid . '_' . getmypid();
        $addGame($mid, $g);
        if ($r === 'D') MatchRecordGameDraw(R, $mid, $g, 5);
        else MatchRecordGameResult(R, $mid, $g, $r, 5, [$r]);
    }
    return MatchRead(R, $mid);
};

echo "Bo1 drawn\n";
$m = $play($newMatch('bo1'), ['D']);
check(($m['state'] ?? '') === 'complete', 'a drawn Bo1 completes the match');
check(intval($m['winner'] ?? -9) === 0 && ($m['winners'] ?? null) === [], 'no match winner');
check(!empty($m['matchDraw']), 'matchDraw is set');
check(!empty($m['games'][0]['draw']) && ($m['games'][0]['winner'] ?? null) === 0 && ($m['games'][0]['winners'] ?? null) === [], 'the game record: draw, winner 0, no winners');
check(!empty($m['games'][0]['statsRecorded']), 'the drawn game is sealed');
check(intval($m['wins']['1'] ?? 0) === 0 && intval($m['wins']['2'] ?? 0) === 0, 'no game wins');
check(end($deckStats) === 0, 'deck stats are told about the draw (seat 0)');

echo "Bo3 outcomes\n";
$m = $play($newMatch('bo3'), [1, 2, 'D']);
check(($m['state'] ?? '') === 'complete' && !empty($m['matchDraw']) && intval($m['winner']) === 0, 'W-L-D is a drawn match');
$m = $play($newMatch('bo3'), ['D', 2, 2]);
check(($m['state'] ?? '') === 'complete' && intval($m['winner']) === 2 && empty($m['matchDraw']), 'D-W-W: that seat wins');
$m = $play($newMatch('bo3'), ['D', 'D', 1]);
check(($m['state'] ?? '') === 'complete' && intval($m['winner']) === 1 && ($m['winners'] ?? null) === [1] && empty($m['matchDraw']), 'D-D-W: that seat wins after game 3 (more game wins)');
$m = $play($newMatch('bo3'), [1, 'D', 1]);
check(($m['state'] ?? '') === 'complete' && intval($m['winner']) === 1, 'W-D-W: that seat wins');
$m = $play($newMatch('bo3'), [1, 'D']);
check(($m['state'] ?? '') !== 'complete', 'W-D: two games, still going');
$m = $play($newMatch('bo3'), ['D', 1, 2]);
check(($m['state'] ?? '') === 'complete' && !empty($m['matchDraw']) && intval($m['winner']) === 0, 'D-W-L (last game a WIN) is a drawn match too');
$m = $play($newMatch('bo3'), ['D', 'D', 'D']);
check(($m['state'] ?? '') === 'complete' && !empty($m['matchDraw']), 'D-D-D is a drawn match');

echo "Idempotent\n";
$drawStats = function () use (&$deckStats) { return count(array_filter($deckStats, fn($s) => $s === 0)); };
$mid = $newMatch('bo1'); $addGame($mid, 'idem_' . getmypid()); $ds0 = $drawStats();
MatchRecordGameDraw(R, $mid, 'idem_' . getmypid(), 5); MatchRecordGameDraw(R, $mid, 'idem_' . getmypid(), 5);
$m = MatchRead(R, $mid);
check(count($m['games']) === 1 && $drawStats() === $ds0 + 1, 'recording a draw twice records it once (one deck-stats call)');

echo "After-action hook: a drawn game in a Bo3 goes to sideboarding with a RANDOM first player\n";
$mid = $newMatch('bo3'); $g1 = 'hook1_' . getmypid(); $addGame($mid, $g1);
$GLOBALS['tmdVars'] = '';
DecisionQueueController::StoreVariable('GAMEOVER_WINNER', '-1');
DecisionQueueController::StoreVariable('GAMEOVER_WINNERS', '');
check(MatchGetGameIsDraw() === true && MatchGetGameWinner() === 0 && MatchGetGameWinners() === [], 'Core reads the draw sentinel');
check(MatchReplayIsGameOver() === true, 'the replay layer sees a drawn game as over');
MatchAfterActionHook(R, $g1);
$m = MatchRead(R, $mid);
check(!empty($m['games'][0]['draw']), 'the hook recorded the draw');
check(($m['state'] ?? '') === 'sideboarding' && array_key_exists('pendingFirstPlayer', $m) && intval($m['pendingFirstPlayer']) === 0, 'sideboarding, pendingFirstPlayer 0 (random)');
$ds1 = $drawStats();
MatchAfterActionHook(R, $g1);   // a sealed game re-ending changes nothing
check(count(MatchRead(R, $mid)['games']) === 1 && $drawStats() === $ds1, 'a sealed drawn game re-ending changes nothing');
$GLOBALS['tmdVars'] = '';
$before = count($setupOpts);
MatchSpawnNextGameWithDecks(R, $mid, intval($m['pendingFirstPlayer']), $g1, [1 => ['A'], 2 => ['B']]);
check(count($setupOpts) === $before + 1 && array_key_exists('forcedFirstPlayer', $setupOpts[$before]) && $setupOpts[$before]['forcedFirstPlayer'] === null, 'game 2 is set up with NO forced first player');

echo "Rematch / convert after a draw: random first player\n";
$mid = $newMatch('bo1'); $play($mid, ['D']);
MatchRequestRematch(R, $mid, 1, 1, false); MatchRequestRematch(R, $mid, 2, 1, false);
$before = count($setupOpts);
$nid = MatchAcceptRematch(R, $mid);
check(is_string($nid) && isset($setupOpts[$before]) && array_key_exists('forcedFirstPlayer', $setupOpts[$before]) && $setupOpts[$before]['forcedFirstPlayer'] === null, 'a rematch of a drawn match forces no first player');
$mid = $newMatch('bo1'); $play($mid, ['D']);
MatchRequestConvertToBo3(R, $mid, 1); MatchRequestConvertToBo3(R, $mid, 2);
MatchAcceptConvertToBo3(R, $mid);
$m = MatchRead(R, $mid);
check(intval($m['bestOf']) === 3 && !empty($m['games'][0]['draw']) && ($m['state'] ?? '') === 'sideboarding', 'convert keeps game 1 as a draw and sideboards');
check(array_key_exists('pendingFirstPlayer', $m) && intval($m['pendingFirstPlayer']) === 0, 'convert after a draw: pendingFirstPlayer 0 (random)');
check(!array_key_exists('matchDraw', $m), 'convert clears the drawn-match flag (the series is live again)');

echo "NEGATIVE: decided matches are unchanged\n";
$m = $play($newMatch('bo3'), [1, 1]);
check(($m['state'] ?? '') === 'complete' && intval($m['winner']) === 1 && !array_key_exists('matchDraw', $m) && !array_key_exists('draw', $m['games'][0]), 'a 2-0 Bo3 has no draw keys at all');
$mid = $newMatch('bo3'); $play($mid, [2]);
$m = MatchRead(R, $mid);
MatchBeginSideboarding(R, $mid, 1);
check(intval(MatchRead(R, $mid)['pendingFirstPlayer']) === 1, 'a decided game still sideboards with the loser first');
MatchBeginSideboarding(R, $mid, 7);
check(intval(MatchRead(R, $mid)['pendingFirstPlayer']) === 1, 'an out-of-range seat still defaults to P1');

exec('rm -rf ' . escapeshellarg(MatchRepoRoot() . '/' . R));
echo $fails === 0 ? "PASS\n" : "$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
