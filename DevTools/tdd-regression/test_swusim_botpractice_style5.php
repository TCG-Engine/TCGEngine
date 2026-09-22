<?php
// RUN VIA CLI:
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 \
//     php -d xdebug.mode=off DevTools/tdd-regression/test_swusim_botpractice_style5.php
//
// ── WHY THIS FILE EXISTS ─────────────────────────────────────────────────────────────────────────
// Since 2026-09-17 the Arenabot menu's "Bot play style" select (SharedUI/Sites/SWUSim/MainMenu.php) offers the FIVE
// archetypes (hyperaggro, softaggro, midrange, softcontrol, hardcontrol — SWUSim/Custom/BotArchetypes.php), but
// APIs/Lobbies/JoinQueue.php and SWUSim/CreateGame.php still whitelisted only the legacy three (aggro | normal |
// control) and turned anything else into 'normal'. So Hyper Aggro, Soft Aggro, Soft Control and Hard Control all
// silently played a MIDRANGE bot; only "Midrange" worked, by accident ('normal' is its alias). Found 2026-09-22.
// test_swusim_botpractice_style.php covers the legacy names; this file covers the five, end to end through the REAL
// endpoint, the same way.

$ROOT = dirname(__DIR__, 2);
$BASEURL = 'http://localhost/TCGEngine/APIs/Lobbies/JoinQueue.php';
$deckA = @file_get_contents($ROOT . '/SWUSim/Tests/BotFixtures/premier_deck_a.txt');
$deckB = @file_get_contents($ROOT . '/SWUSim/Tests/BotFixtures/premier_deck_b.txt');

$checks = [];
$createdGames = [];
$note = function ($k, $v) use (&$checks) { $checks[$k] = ($v === true); };

if (!is_string($deckA) || !is_string($deckB)) {
    echo "FAIL: bot fixture decks missing under SWUSim/Tests/BotFixtures/\n";
    exit(1);
}

function BotStyle5PostQueue(string $url, array $fields): ?object {
    $ctx = stream_context_create(['http' => [
        'method'        => 'POST',
        'header'        => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content'       => http_build_query($fields),
        'timeout'       => 60,
        'ignore_errors' => true,
    ]]);
    $raw = @file_get_contents($url, false, $ctx);
    if (!is_string($raw)) return null;
    $j = json_decode($raw);
    return is_object($j) ? $j : null;
}

require_once $ROOT . '/Core/EngineActionRunner.php';
EngineLoadRootRuntime('SWUSim');
require_once $ROOT . '/SWUSim/CreateGame.php';
require_once $ROOT . '/SWUSim/BotHeuristic.php';
$swuDir = $ROOT . '/SWUSim/';

function BotStyle5ReadGame(string $swuDir, $name): array {
    global $gameName, $playerID;
    $gameName = $name;
    $playerID = 1;
    ParseGamestate($swuDir);
    $stored = DecisionQueueController::GetVariable('SWUBotProfile');
    return ['stored' => $stored === null ? '' : strval($stored), 'active' => SWUBotActiveChooserProfile(2)];
}

// Every value the menu's select can send (read from the menu itself, so the test follows the menu).
$menu = file_get_contents($ROOT . '/SharedUI/Sites/SWUSim/MainMenu.php');
preg_match("/foreach \(\[('hyperaggro'.*?)\] as \\\$sid/s", $menu, $m);
preg_match_all("/'([a-z]+)' =>/", $m[1] ?? '', $ids);
$styles = $ids[1] ?? [];
$note('the menu offers the five archetypes', $styles === ['hyperaggro', 'softaggro', 'midrange', 'softcontrol', 'hardcontrol']);

foreach ($styles as $style) {
    $res = BotStyle5PostQueue($BASEURL, [
        'rootName' => 'SWUSim', 'format' => 'botpractice', 'queueType' => 'bo1',
        'deckLink' => $deckA, 'deckLink2' => $deckB, 'botStyle' => $style,
    ]);
    $note("$style: endpoint reports success", is_object($res) && ($res->success ?? false) === true);
    if (!is_object($res) || empty($res->gameName)) continue;
    $createdGames[] = $res->gameName;
    $g = BotStyle5ReadGame($swuDir, $res->gameName);
    $note("$style: stored profile is heuristic-$style (got {$g['stored']})", $g['stored'] === "heuristic-$style");
    $note("$style: seat 2 would choose with heuristic-$style", $g['active'] === "heuristic-$style");
    $note("$style: that profile is registered", isset($GLOBALS['SWUBotChoosers']["heuristic-$style"]));
}
// Case is still ignored for the new names.
$res = BotStyle5PostQueue($BASEURL, ['rootName' => 'SWUSim', 'format' => 'botpractice', 'queueType' => 'bo1',
    'deckLink' => $deckA, 'deckLink2' => $deckB, 'botStyle' => 'HardControl']);
if (is_object($res) && !empty($res->gameName)) {
    $createdGames[] = $res->gameName;
    $note('HardControl (mixed case) → heuristic-hardcontrol', BotStyle5ReadGame($swuDir, $res->gameName)['stored'] === 'heuristic-hardcontrol');
} else {
    $note('HardControl (mixed case) → endpoint reports success', false);
}

foreach ($createdGames as $gn) {
    $dir = $swuDir . 'Games/' . $gn;
    if (is_dir($dir)) {
        array_map('unlink', glob($dir . '/*') ?: []);
        @rmdir($dir);
    }
}

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
if (empty($checks)) { echo "FAIL: no checks ran (endpoint unreachable?)\n"; exit(1); }
if ($fails) {
    echo "FAIL (" . count($fails) . "/" . count($checks) . "):\n";
    foreach ($fails as $f) echo "  - $f\n";
    exit(1);
}
echo "PASS (" . count($checks) . " checks)\n";
exit(0);
