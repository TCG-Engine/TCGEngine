<?php
// Regenerate SWUSim/Custom/BotDeckLabels.json from the OWNER-LABELLED fixture GROUPS below. Run this after the
// owner labels or relabels a fixture, or adds a group; the runtime (the Arenabot pre-con picker and the deck-style
// classifier) reads only the JSON, never the test folders.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off SWUSim/DevTools/regen-deck-labels.php
chdir(dirname(__DIR__, 2));
// ⚠ The card dictionaries are required for the 'name' field: SWUBotDeckDisplayName() needs CardTitle/
// CardRarity/CardHp/CardAspect, and it returns '' rather than a half-built label when they are missing —
// so without this include every deck silently got an EMPTY name. BotDeckStyle.php does not pull them in.
require_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
require_once './SWUSim/Custom/BotDeckStyle.php';

const SWU_LABEL_STYLES = ['hyperaggro', 'softaggro', 'midrange', 'softcontrol', 'hardcontrol'];
// The Arenabot pre-con GROUPS (owner 2026-10-01), in the order the picker shows them: one fixture directory each.
// 'classify' — whether the deck-style classifier may label a player's list from this group's decks. Only the
// reviewed tournament set does (bot_decklabels_test); a creator's predictions are offered to play against, not
// used as ground truth.
const SWU_PRECON_GROUPS = [
    ['id' => 'ash-meta-2026-09',          'label' => 'ASH Meta September 2026',   'classify' => true],
    ['id' => 'force-fam-HMW-predictions', 'label' => 'Force Fam HMW Predictions', 'classify' => false],
];
$out = []; $groups = [];
foreach (SWU_PRECON_GROUPS as $g) {
    $groups[] = $g;
    foreach (glob('./SWUSim/Tests/BotFixtures/' . $g['id'] . '/*.txt') ?: [] as $path) {
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
        // SWUBotFixtureDisplayName adds " By <Author>" when the fixture has a "# Author:" line (owner 2026-10-01).
        // 'key' is unique across groups (the same file name can sit in two groups — ahsoka-tano_ash_yellow does).
        $out[] = ['key' => $g['id'] . '--' . basename($path, '.txt'), 'group' => $g['id'],
                  'file' => basename($path, '.txt'), 'name' => SWUBotFixtureDisplayName($deck),
                  'author' => $deck['author'], 'leader' => $deck['leader'], 'base' => $deck['base'],
                  'style' => $style, 'cards' => $deck['cards'], 'total' => array_sum($deck['cards'])];
    }
}
// Group order first (as listed above), then by file within a group.
$order = array_flip(array_column(SWU_PRECON_GROUPS, 'id'));
usort($out, fn($a, $b) => [$order[$a['group']], $a['file']] <=> [$order[$b['group']], $b['file']]);
file_put_contents('./SWUSim/Custom/BotDeckLabels.json',
    json_encode(['generated' => date('Y-m-d'), 'groups' => $groups, 'decks' => $out], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
$per = array_count_values(array_column($out, 'group'));
echo 'wrote ' . count($out) . ' labelled decks: ' . json_encode($per) . "\n";
