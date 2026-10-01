<?php
// SUPERSET research mode (owner 2026-10-01): every meta fixture carries its 10-card sideboard as a `Sideboard`
// section, a normal run ignores it, and --superset folds it into the main deck.
// Asserted through SWUResolveDeckInput — the resolver LoadPlayerDeck (CreateGame.php) runs on the deck text the
// self-play harness hands to `new Player()` — not through the fixture parser alone, so this tests what a game
// actually loads.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_superset_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/Custom/DeckImport.php';
include_once './SWUSim/Custom/BotDeckStyle.php';

$files = glob('./SWUSim/Tests/BotFixtures/meta-2026-09/*.txt') ?: [];
$check(count($files) === count(json_decode((string)file_get_contents('./SWUSim/Custom/BotDeckLabels.json'), true)['decks'] ?? []),
    'every labelled meta fixture is covered (' . count($files) . ' files)');

$noSide = $wrongMain = $sideInGame = $badFold = $refusedOk = [];
foreach ($files as $path) {
    $name = basename($path, '.txt');
    $text = (string)file_get_contents($path);
    $parsed = SWUBotDeckFromFixtureText($text);
    $mainN = array_sum($parsed['cards']);
    if (array_sum($parsed['sideboard']) !== 10) $noSide[] = $name . ':' . array_sum($parsed['sideboard']);

    // GAME 1 — what a normal run loads: the main deck only; the sideboard stays out of the Deck zone.
    $g1 = SWUResolveDeckInput($text);
    if (empty($g1['success']) || count($g1['mainDeck']) !== $mainN) $wrongMain[] = "$name:" . count($g1['mainDeck'] ?? []) . "/$mainN";
    if (count($g1['sideboard'] ?? []) !== 10) $sideInGame[] = "$name:" . count($g1['sideboard'] ?? []);

    // SUPERSET — the same resolver on the folded text: main + sideboard, card for card, and nothing left over.
    $folded = SWUBotFixtureSuperset($text);
    $ss = $folded === null ? null : SWUResolveDeckInput($folded);
    $want = array_merge($g1['mainDeck'] ?? [], $g1['sideboard'] ?? []); sort($want);
    $got = $ss['mainDeck'] ?? []; sort($got);
    if ($ss === null || empty($ss['success']) || $got !== $want || !empty($ss['sideboard'])
        || $ss['leader'] !== $g1['leader'] || $ss['base'] !== $g1['base']) $badFold[] = $name;
}
$check(empty($noSide), 'every meta fixture has a 10-card Sideboard section: ' . json_encode($noSide));
$check(empty($wrongMain), 'GAME 1: the resolver loads the main deck only, sideboard excluded: ' . json_encode($wrongMain));
$check(empty($sideInGame), 'GAME 1: the 10 sideboard cards resolve as sideboard, not main: ' . json_encode($sideInGame));
$check(empty($badFold), 'SUPERSET: the folded deck is exactly main + sideboard, same leader/base, empty sideboard: ' . json_encode($badFold));

// Spot-check sizes the owner named: a 50-card list becomes 60, a Data Vault 60 becomes 70.
$size = fn(string $f) => count(SWUResolveDeckInput(SWUBotFixtureSuperset((string)file_get_contents("./SWUSim/Tests/BotFixtures/meta-2026-09/$f.txt")))['mainDeck']);
$check($size('darth-vader_jtl_yellow') === 60, 'Vader yellow 50 + 10 = 60 → ' . $size('darth-vader_jtl_yellow'));
$check($size('grand-admiral-thrawn_jtl_data-vault') === 70, 'Thrawn Data Vault 60 + 10 = 70 → ' . $size('grand-admiral-thrawn_jtl_data-vault'));
$check($size('the-mandalorian_ash_colossus') === 75, 'Mando Colossus 65 + 10 = 75 (with the 3 Zebs restored) → ' . $size('the-mandalorian_ash_colossus'));

// A deck WITHOUT a sideboard is refused (null), never silently played as its game-1 list.
$weak = glob('./SWUSim/Tests/BotFixtures/weak-2026-09/*.txt')[0] ?? '';
$check($weak !== '' && SWUBotFixtureSuperset((string)file_get_contents($weak)) === null,
    'a fixture with no Sideboard section folds to null, so --superset refuses it: ' . basename($weak));

bot_test_finish();
