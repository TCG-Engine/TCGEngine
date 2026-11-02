<?php
// RUN VIA CLI:
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 \
//     php -d apc.enable_cli=1 -d xdebug.mode=off DevTools/tdd-regression/test_swusim_arenabot_card_pool.php
//
// Arenabot's card pool, END TO END through the real endpoint (APIs/Lobbies/JoinQueue.php), over the container's loopback,
// the transport the menu uses — the pattern of test_swusim_botpractice_joinqueue_lobby.php, and for the same reason: a
// lobby built in-process never proves what the endpoint does. Spec: docs/superpowers/specs/2026-09-16-swusim-format-menu-design.md §2.
// Fixture legality was verified with SWUResolveDeckInput + SWUCheckFormat on 2026-09-16.
error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING & ~E_NOTICE);
$ROOT = dirname(__DIR__, 2);
$URL = 'http://localhost/TCGEngine/APIs/Lobbies/JoinQueue.php';
// all-SOR, so Premier ✗ / Eternal ✓ / Open ✓
$sor = <<<'DECK'
Leader
1 SOR_009
Base
1 SOR_022
Deck
3 SOR_095
3 SOR_098
2 SOR_240
2 SOR_239
2 SOR_243
2 SOR_116
2 SOR_113
2 SOR_108
1 SOR_242
1 SOR_100
2 SOR_093
1 SOR_094
2 SOR_248
1 SOR_096
3 SOR_237
2 SOR_111
2 SOR_112
1 SOR_099
2 SOR_250
3 SOR_124
2 SOR_123
2 SOR_127
2 SOR_103
3 SOR_120
2 SOR_121
DECK;
// Ahsoka Tano (ASH) Blue, Premier ✓
$ahsoka = <<<'DECK'
Leader
1 ASH_009
Base
1 JTL_019
Deck
3 SEC_046
3 LOF_093
3 LAW_037
3 SEC_111
3 ASH_079
3 LAW_039
3 LAW_038
3 ASH_248
3 SEC_101
3 ASH_062
2 ASH_056
2 LOF_096
2 ASH_077
3 SEC_099
3 JTL_095
3 JTL_250
2 LAW_041
1 SEC_179
2 LAW_127
DECK;
// Director Krennic (LAW) Blue Splash, Premier ✓
$krennic = <<<'DECK'
Leader
1 LAW_008
Base
1 LAW_020
Deck
3 ASH_118
3 ASH_116
3 JTL_032
3 LAW_159
3 LAW_097
3 ASH_079
3 ASH_097
3 LOF_059
3 ASH_053
2 SEC_087
2 ASH_048
2 LAW_039
2 ASH_133
1 SEC_068
1 LAW_149
3 ASH_052
2 LAW_101
1 JTL_041
3 JTL_043
3 LAW_044
1 SEC_078
1 JTL_121
2 LOF_091
DECK;

$post = function (array $fields) use ($URL): ?object {
    $ctx = stream_context_create(['http' => ['method' => 'POST', 'timeout' => 60, 'ignore_errors' => true,
        'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => http_build_query($fields + ['rootName' => 'SWUSim', 'format' => 'botpractice', 'queueType' => 'bo1'])]]);
    $j = json_decode(strval(@file_get_contents($URL, false, $ctx)));
    return is_object($j) ? $j : null;
};
require_once $ROOT . '/Core/EngineActionRunner.php';
EngineLoadRootRuntime('SWUSim');
require_once $ROOT . '/SWUSim/CreateGame.php';
$created = [];
$read = function ($r) use ($ROOT, &$created): array {
    if (!is_object($r) || empty($r->gameName)) return ['pool' => null, 'mode' => null];
    $created[] = $r->gameName;
    global $gameName, $playerID;
    $gameName = $r->gameName; $playerID = 1;
    ParseGamestate($ROOT . '/SWUSim/');
    return ['pool' => SWUGameCardPool(), 'mode' => SWUGameMode()];
};
$checks = [];
$ok = fn($r) => is_object($r) && ($r->success ?? false) === true;
$msg = fn($r) => is_object($r) ? strval($r->message ?? '') : '';

// A — Premier, two legal decks: allowed, and the game records premier.
$rA = $post(['deckLink' => $ahsoka, 'deckLink2' => $krennic, 'cardPool' => 'premier']);
$gA = $read($rA);
$checks['A: Premier with two legal decks starts']          = $ok($rA);
$checks['A: the game records card pool premier']           = $gA['pool'] === 'premier';
$checks['A: the game is still an Arenabot game']           = $gA['mode'] === 'botpractice';
// B — Premier, an illegal PLAYER deck: refused, naming the player.
$rB = $post(['deckLink' => $sor, 'deckLink2' => $krennic, 'cardPool' => 'premier']);
$checks['B: an illegal player deck is refused']            = !$ok($rB) && str_starts_with($msg($rB), 'Your deck is not legal in Premier:');
// C — Premier, an illegal BOT deck: refused, naming the bot. The bot's deck was never validated before this.
$rC = $post(['deckLink' => $ahsoka, 'deckLink2' => $sor, 'cardPool' => 'premier']);
$checks["C: an illegal bot deck is refused"]               = !$ok($rC) && str_starts_with($msg($rC), "The bot's deck is not legal in Premier:");
// D — Premier, blank bot deck: the bot mirrors the (legal) player deck.
$rD = $post(['deckLink' => $ahsoka, 'cardPool' => 'premier']);
$checks['D: a blank bot deck mirrors the legal player deck'] = $ok($rD) && $read($rD)['pool'] === 'premier';
// E — Open: anything goes, as Bot Practice always allowed.
$rE = $post(['deckLink' => $sor, 'deckLink2' => $sor, 'cardPool' => 'open']);
$checks['E: Open accepts decks that are illegal elsewhere']  = $ok($rE) && $read($rE)['pool'] === 'open';
// F — no cardPool at all (an older client): behaves as Open.
$rF = $post(['deckLink' => $sor, 'deckLink2' => $sor]);
$checks['F: a missing cardPool behaves as Open']             = $ok($rF) && $read($rF)['pool'] === 'open';
// G — an unknown pool: refused, never widened to Open.
$rG = $post(['deckLink' => $sor, 'deckLink2' => $sor, 'cardPool' => 'twinsuns']);
$checks['G: an unknown cardPool is refused']                 = !$ok($rG) && $msg($rG) === 'Unknown card pool for Arenabot.';
// H — Eternal: SOR decks are legal there.
$rH = $post(['deckLink' => $sor, 'deckLink2' => $sor, 'cardPool' => 'eternal']);
$checks['H: Eternal accepts SOR decks and records eternal']  = $ok($rH) && $read($rH)['pool'] === 'eternal';

foreach ($created as $g) {
    array_map('unlink', glob("$ROOT/SWUSim/Games/$g/*") ?: []);
    @rmdir("$ROOT/SWUSim/Games/$g");
}
$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
exit(empty($fails) ? 0 : 1);
