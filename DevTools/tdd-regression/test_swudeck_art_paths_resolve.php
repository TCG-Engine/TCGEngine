<?php
// RUN VIA CLI:
//   docker exec -w /var/www/html/TCGEngine otmtcge-swustats-web-server-1 php DevTools/tdd-regression/test_swudeck_art_paths_resolve.php
//
// Every image path SWUDeck builds must resolve to a file that EXISTS, for every id scheme that can
// reach it. This test exists because the SET_NNN re-key (2026-08-04) shipped with a fully green
// suite and a visibly broken deckbuilder three separate times:
//   1. the client JS accessors had no either-key façade  -> every browse tile hidden
//   2. art filenames are the OLD key                     -> every browse tile 404'd
//   3. mock art is stored as mock_<id>.webp              -> preview leaders 404'd on the main menu
// Asserting a URL STRING catches none of those. Assert the file.
//
// Three id schemes can reach a path builder today:
//   - FFG UID   ("2579145458") — decks saved before the re-key
//   - SET_NNN   ("SOR_005")    — the dictionaries, and decks saved after it
//   - mock id   ("HMW_004")    — preview cards, whose art is mock_-prefixed and has no UUID
header('Content-Type: text/plain');
require_once __DIR__ . '/../../SWUDeck/GeneratedCode/GeneratedCardDictionaries.php';
require_once __DIR__ . '/../../AppCore/SWU/Formats.php';
require_once __DIR__ . '/../../SWUDeck/Custom/DeckFormats.php';
require_once __DIR__ . '/../../AppCore/SWU/MockCardMerge.php';
require_once __DIR__ . '/../../AppCore/SWU/CardImagePath.php';

$checks = [];
$root = __DIR__ . '/../../';
$exists = fn($webPath) => is_file($root . ltrim(str_replace('/TCGEngine/', '', $webPath), '/'));

// A representative id of each scheme, derived rather than hardcoded where possible.
$mockIDs = array_keys(SWULoadMockCards());
$mockID  = null;
foreach ($mockIDs as $m) { if (!preg_match('/_T\d{2}$/', $m)) { $mockID = $m; break; } }

$samples = array_filter([
    'SET_NNN leader' => 'SOR_005',
    'SET_NNN base'   => 'SOR_029',
    'FFG UID'        => '2579145458',
    'preview mock'   => $mockID,
]);

foreach ($samples as $label => $id) {
    $checks["$label: full art resolves"]   = $exists(SWUDeckWebpUrl($id));
    $checks["$label: leader crop resolves"] = $exists(SWUDeckLeaderCropUrl($id));
}

// Every card the browse panes can show must have a tile, or it renders as a broken "Card" box.
$all = GetAllCardIds();
$mocks = SWULoadMockCards();
$missingTiles = [];
foreach ($all as $id) {
    if (!is_file(SWUCardImageFsPath($id, 'tile'))) $missingTiles[] = $id;
}
$checks['every browse-catalog card has a tile'] = count($missingTiles) === 0;
if ($missingTiles) echo "MISSING TILES (" . count($missingTiles) . "): " . implode(', ', array_slice($missingTiles, 0, 10)) . "\n";

// Tokens are not deckbuildable and must stay out of the catalog entirely.
//
// This checked only MOCK tokens until 2026-08-05, and that is exactly why 11 OFFICIAL tokens leaked
// into deck search while the suite stayed green: the generator's guard tested $card->id, which for
// SWUDeck is the UUID and never matches _T##, so only mocks (which carry their SET_NNN as $card->id)
// were ever excluded. Assert the whole class, not the subset you happen to be thinking about.
$catalogTokens = array_values(array_filter($all, fn($i) => preg_match('/_T\d{2}$/', (string)$i)));
$checks['no token is in the browse catalog'] = count($catalogTokens) === 0;
if ($catalogTokens) echo "TOKENS IN CATALOG: " . implode(', ', $catalogTokens) . "\n";

// ...and the converse: they MUST be in the dictionary, or ~48k token stat rows are unresolvable and
// the migration drops them. The two requirements pull in opposite directions; assert both.
$dictTokens = array_filter(array_keys($GLOBALS['titleData'] ?? []), fn($i) => preg_match('/_T\d{2}$/', (string)$i));
$checks['tokens ARE in the dictionary'] = count($dictTokens) >= 20;
$checks['a token UUID resolves'] = CardIDLookup('8752877738') === 'SOR_T02';   // Shield

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
