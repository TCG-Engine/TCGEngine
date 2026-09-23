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
$sor     = file_get_contents($ROOT . '/SWUSim/Tests/BotFixtures/premier_deck_a.txt');                     // Premier ✗, Eternal ✓
$ahsoka  = file_get_contents($ROOT . '/SWUSim/Tests/BotFixtures/meta-2026-09/ahsoka_blue.txt');     // Premier ✓
$krennic = file_get_contents($ROOT . '/SWUSim/Tests/BotFixtures/meta-2026-09/krennic_splash.txt'); // Premier ✓

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
