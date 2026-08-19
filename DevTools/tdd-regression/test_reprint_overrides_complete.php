<?php
// Two printings that share a title+subtitle are the same card and MUST be linked in Overrides.php.
// Unlinked, they are two separate cards to every system: separate stats rows, no shared legality, and
// no display substitution — all silently. That silence is how a "why didn't Open Fire update?" report
// gets filed months after the set ships.
$root = realpath(__DIR__ . '/../..');
require_once $root . '/SWUDeck/GeneratedCode/GeneratedCardDictionaries.php';
require_once $root . '/AppCore/SWU/Overrides.php';
global $titleData;

// Tokens are not deckbuildable and legitimately repeat titles across sets.
$isToken = fn($id) => (bool)preg_match('/_T\d{2}$/', (string)$id);

$byName = [];
foreach ($titleData as $id => $t) {
    if ($isToken($id)) continue;
    // Title PLUS subtitle plus type: leaders especially share titles with their unit-side namesakes,
    // and those are genuinely different cards.
    $key = strtolower(trim($t . '|' . (string)CardSubtitle($id) . '|' . (string)CardType($id)));
    $byName[$key][] = $id;
}

$unlinked = [];
foreach ($byName as $key => $ids) {
    if (count($ids) < 2) continue;
    $canons = array_unique(array_map('CardIDOverride', $ids));
    if (count($canons) > 1) $unlinked[] = $key . '  ->  ' . implode(', ', $ids);
}

if ($unlinked) {
    fwrite(STDERR, "FAIL: printings share a name but are not linked in Overrides.php:\n  - "
        . implode("\n  - ", $unlinked) . "\n");
    exit(1);
}
echo "PASS (all same-named printings are linked)\n";
