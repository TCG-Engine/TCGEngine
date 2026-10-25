<?php
// A rematch is the same two players: the new match keeps each seat's account, deck identity, deck link and cosmetics
// (MatchAcceptRematch used to carry only the deck and the seat key, so a rematch's games belonged to no account).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_match_rematch_carries_seats.php
include_once __DIR__ . '/../../Core/Match/MatchFlow.php';

MatchRegisterHooks('MatchRematchSeatSim', [
    'resolveLobbyDecks' => fn($lobby) => [],
    'validateDeck' => fn($d, $f) => true,
    'setupGame' => function ($lobby, $opts) {
        $g = 'MRSgame' . getmypid() . '_' . mt_rand();
        @mkdir(__DIR__ . '/../../MatchRematchSeatSim/Games/' . $g, 0777, true);
        return $g;
    },
]);

$seat = fn(int $s) => ['originalDeck' => ["D$s"], 'authKey' => "k$s", 'userId' => 300 + $s, 'deckIdentity' => "ident-$s",
    'deckLink' => "https://example.test/deck/$s", 'cosmetics' => ['sleeve' => "s$s"]];
@mkdir(__DIR__ . '/../../MatchRematchSeatSim/Games/mrs1', 0777, true);
$mid = MatchCreate('MatchRematchSeatSim', 'premier', 'bo1', [1 => $seat(1), 2 => $seat(2)]);
MatchRecordGameResult('MatchRematchSeatSim', $mid, 'mrs1', 1, 2);
MatchRequestRematch('MatchRematchSeatSim', $mid, 1, 1, false);
MatchRequestRematch('MatchRematchSeatSim', $mid, 2, 1, false);
$newId = MatchAcceptRematch('MatchRematchSeatSim', $mid);
$nm = is_string($newId) ? MatchRead('MatchRematchSeatSim', $newId) : [];

$fails = 0;
foreach ([1, 2] as $s) {
    $p = $nm['players'][strval($s)] ?? [];
    $got = [$p['userId'] ?? null, $p['deckIdentity'] ?? null, $p['deckLink'] ?? null, $p['cosmetics'] ?? null, $p['authKey'] ?? null];
    $want = [300 + $s, "ident-$s", "https://example.test/deck/$s", ['sleeve' => "s$s"], "k$s"];
    $ok = $got === $want;
    if (!$ok) $fails++;
    echo ($ok ? 'PASS' : 'FAIL') . ": the rematch keeps seat $s's account, deck identity, deck link and cosmetics" . ($ok ? '' : '  got ' . json_encode($got)) . "\n";
}

foreach ([$mid, $newId] as $x) if (is_string($x) && $x !== '') @unlink(MatchPath('MatchRematchSeatSim', $x));
exec('rm -rf ' . escapeshellarg(__DIR__ . '/../../MatchRematchSeatSim'));
echo "\n" . ($fails === 0 ? 'PASS: test_match_rematch_carries_seats.php — all 2 checks passed' : "FAIL: test_match_rematch_carries_seats.php — $fails of 2 checks failed") . "\n";
exit($fails === 0 ? 0 : 1);
