<?php
// Regenerate SWUSim/Custom/BotDeckLabels.json from the OWNER-LABELLED fixtures (meta-2026-09/). Run this after the
// owner labels or relabels a fixture; the runtime classifier reads only the JSON, never the test folders.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off SWUSim/DevTools/regen-deck-labels.php
chdir(dirname(__DIR__, 2));
// ⚠ The card dictionaries are required for the 'name' field: SWUBotDeckDisplayName() needs CardTitle/
// CardRarity/CardHp/CardAspect, and it returns '' rather than a half-built label when they are missing —
// so without this include every deck silently got an EMPTY name. BotDeckStyle.php does not pull them in.
require_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
require_once './SWUSim/Custom/BotDeckStyle.php';

const SWU_LABEL_STYLES = ['hyperaggro', 'softaggro', 'midrange', 'softcontrol', 'hardcontrol'];
$out = [];
foreach (glob('./SWUSim/Tests/BotFixtures/meta-2026-09/*.txt') ?: [] as $path) {
    $text = (string)file_get_contents($path);
    // '# Style:' is how the BOT PILOTS the deck (measured); '# DeckStyle:' is what archetype the deck IS (the
    // owner's read, what the menu preselects). They are usually the same; where they differ, the classifier takes
    // DeckStyle. Owner, 2026-09-22: Maul Blue Force is piloted midrange but reads as soft control.
    preg_match('/^# DeckStyle:\s*(\S+)/m', $text, $dm);
    preg_match('/^# Style:\s*(\S+)/m', $text, $m);
    $style = strtolower(strval($dm[1] ?? $m[1] ?? ''));
    if (!in_array($style, SWU_LABEL_STYLES, true)) {
        fwrite(STDERR, "REFUSED: " . basename($path) . " has no archetype '# Style:' header (got '" . strval($m[1] ?? '') . "')\n");
        exit(1);
    }
    $deck = SWUBotDeckFromFixtureText($text);
    if ($deck['leader'] === '' || empty($deck['cards'])) {
        fwrite(STDERR, "REFUSED: " . basename($path) . " has no leader or no deck\n");
        exit(1);
    }
    // 'name' is the Bot Arena display name, derived from the CARD IDs (SWUBotDeckDisplayName,
    // BotDeckStyle.php) and stored here so the menu never has to un-slug the filename — which cannot
    // round-trip, because a hyphen is both the word separator and part of names like Obi-Wan Kenobi.
    $out[] = ['file' => basename($path, '.txt'), 'name' => SWUBotDeckDisplayName($deck['leader'], $deck['base']),
              'leader' => $deck['leader'], 'base' => $deck['base'],
              'style' => $style, 'cards' => $deck['cards'], 'total' => array_sum($deck['cards'])];
}
usort($out, fn($a, $b) => strcmp($a['file'], $b['file']));
file_put_contents('./SWUSim/Custom/BotDeckLabels.json',
    json_encode(['generated' => date('Y-m-d'), 'decks' => $out], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
echo 'wrote ' . count($out) . " labelled decks\n";
