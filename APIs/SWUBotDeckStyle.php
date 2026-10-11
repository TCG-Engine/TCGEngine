<?php
// The Main Menu's deck-style lookup: resolve a deck link or a pasted list and answer which archetype it is, so
// Arenabot's "Bot play style" select can be preselected (spec 2026-09-22). READ-ONLY: no lobby, no game, no row.
// Guests may call it — the menu is open to guests.
header('Content-Type: application/json');
$root = strval($_POST['rootName'] ?? $_GET['rootName'] ?? '');
if ($root !== 'SWUSim') { echo json_encode(['ok' => false, 'error' => 'unsupported']); exit; }

chdir(dirname(__DIR__));
require_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
require_once './SWUSim/Rl/CardTags.php';
require_once './SWUSim/Custom/BotDeckStyle.php';
require_once './SWUSim/Custom/DeckImport.php';

$input = trim(strval($_POST['deckLink'] ?? ''));
if ($input === '') $input = trim(strval($_POST['deckText'] ?? ''));
if ($input === '') { echo json_encode(['ok' => false, 'error' => 'no deck']); exit; }

// Cache by the input, not by the resolved deck: a repeated link then costs no fetch at all.
$key = 'swu_deckstyle_' . sha1($input);
if (function_exists('apcu_fetch')) {
    $hit = apcu_fetch($key, $found);
    if ($found && is_string($hit)) { echo $hit; exit; }
}

$deck = SWUResolveDeckInput($input);
if (empty($deck['success'])) {
    echo json_encode(['ok' => false, 'error' => strval($deck['message'] ?? 'could not read that deck')]);
    exit;
}
$counts = [];
foreach ((array)($deck['mainDeck'] ?? []) as $id) $counts[strval($id)] = ($counts[strval($id)] ?? 0) + 1;
// Twin Suns lists carry two leaders; the first one is enough to find the leader's labelled decks.
$leader = $deck['leader'] ?? '';
$r = SWUBotDeckStyle(['leader' => is_array($leader) ? strval($leader[0] ?? '') : strval($leader),
                      'base' => strval($deck['base'] ?? ''), 'cards' => $counts]);
if ($r['style'] === null) { echo json_encode(['ok' => false, 'error' => 'no deck']); exit; }

$out = json_encode(['ok' => true, 'style' => $r['style'], 'confidence' => $r['confidence'],
                    'source' => $r['source'], 'reasons' => $r['reasons']]);
if (function_exists('apcu_store')) apcu_store($key, $out, 600);
echo $out;
