<?php
/**
 * Build a prioritized, human-readable semantic-fixture authoring backlog.
 *
 * Joins the non-sensitive implemented-card inventory with generated official
 * card dictionaries and existing fixture metadata. Mechanics are conservative
 * tags inferred from printed effect text; they guide batching, not rules truth.
 *
 * Usage:
 *   php DevTools/build-ga-semantic-backlog.php \
 *     --inventory=Tests/Integration/GrandArchiveSim/implemented-cards.json
 */
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 1);

$repoRoot = dirname(__DIR__);
$rootName = 'GrandArchiveSim';
$inventoryPath = $repoRoot . '/Tests/Integration/GrandArchiveSim/implemented-cards.json';
$outputPath = $repoRoot . '/Tests/Integration/GrandArchiveSim/semantic-coverage-backlog.json';
$markdownPath = $repoRoot . '/Tests/Integration/GrandArchiveSim/semantic-coverage-backlog.md';
$top = 50;
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--inventory=')) $inventoryPath = substr($arg, 12);
    elseif (str_starts_with($arg, '--output=')) $outputPath = substr($arg, 9);
    elseif (str_starts_with($arg, '--markdown=')) $markdownPath = substr($arg, 11);
    elseif (str_starts_with($arg, '--top=')) $top = max(1, intval(substr($arg, 6)));
}

if (!is_file($inventoryPath)) {
    fwrite(STDERR, "Inventory not found: {$inventoryPath}\n");
    exit(1);
}
$inventory = json_decode(file_get_contents($inventoryPath), true);
$implemented = is_array($inventory) ? ($inventory['cards'] ?? $inventory) : null;
if (!is_array($implemented)) {
    fwrite(STDERR, "Inventory must be an array or an object with a cards array.\n");
    exit(1);
}

$dictionaryPath = $repoRoot . "/{$rootName}/GeneratedCode/GeneratedCardDictionaries.php";
if (!is_file($dictionaryPath)) {
    fwrite(STDERR, "Generated card dictionary not found: {$dictionaryPath}; regenerate {$rootName} first.\n");
    exit(1);
}
require_once $dictionaryPath;

function BacklogFixtureLinks($fixtureRoot) {
    $links = [];
    foreach (scandir($fixtureRoot) as $slug) {
        if ($slug === '.' || $slug === '..' || !is_dir($fixtureRoot . '/' . $slug)) continue;
        $metaPath = $fixtureRoot . '/' . $slug . '/meta.json';
        $meta = is_file($metaPath) ? json_decode(file_get_contents($metaPath), true) : [];
        if (!is_array($meta)) continue;
        $contract = is_array($meta['semanticCoverage'] ?? null) ? $meta['semanticCoverage'] : [];
        $cardIds = $contract['testedCards'] ?? $meta['testedCards'] ?? [];
        foreach ((array)$cardIds as $cardId) {
            $cardId = strval($cardId);
            if ($cardId === '') continue;
            $links[$cardId]['fixtures'][] = $slug;
            if (!empty($contract)) $links[$cardId]['semanticFixtures'][] = $slug;
        }
    }
    return $links;
}

function BacklogMechanics($effect) {
    $text = strtolower(trim(html_entity_decode(strip_tags(strval($effect)))));
    $patterns = [
        'cost' => '/\bcosts?\b|\bpay\b|\breserve\b/',
        'targeting' => '/\btarget\b|\bchoose\b/',
        'damage' => '/\bdamage\b/',
        'prevention' => '/\bprevent\b|\breplacement effect\b/',
        'recover' => '/\brecover\b/',
        'draw-discard' => '/\bdraw\b|\bdiscard\b/',
        'zone-movement' => '/\bbanish\b|\bdestroy\b|\breturn\b|\bput\b.*\b(?:hand|graveyard|memory|deck|field)\b/',
        'counter' => '/\bcounter\b/',
        'status' => '/\b(?:stealth|distant|awake|rested|imbued)\b/',
        'token' => '/\btoken\b|\bsummon\b/',
        'combat' => '/\battack\b|\bretaliate\b|\bintercept\b|\bon hit\b/',
        'trigger' => '/\bon enter\b|\bon death\b|\bon attack\b|\bwhenever\b/',
        'condition' => '/\bif\b|\bas long as\b|\bonce per turn\b/',
    ];
    $tags = [];
    foreach ($patterns as $tag => $pattern) if (preg_match($pattern, $text)) $tags[] = $tag;
    return $tags ?: ['unclassified'];
}

$fixtureRoot = $repoRoot . "/Tests/Integration/{$rootName}";
$fixtureLinks = BacklogFixtureLinks($fixtureRoot);
$cards = [];
foreach ($implemented as $entry) {
    if (!is_array($entry)) $entry = ['cardId' => $entry];
    $cardId = strval($entry['cardId'] ?? $entry['card_id'] ?? '');
    if ($cardId === '') continue;
    $effect = function_exists('CardEffect') ? strval(CardEffect($cardId) ?? '') : '';
    $fixtures = array_values(array_unique($fixtureLinks[$cardId]['fixtures'] ?? []));
    $semanticFixtures = array_values(array_unique($fixtureLinks[$cardId]['semanticFixtures'] ?? []));
    $mechanics = BacklogMechanics($effect);
    // Prioritize cards with multiple/listener abilities and known regression
    // fixtures: those have the greatest leverage and lowest fixture setup cost.
    $priority = intval($entry['abilityCount'] ?? 1) * 10
        + intval($entry['listenerCount'] ?? 0) * 8
        // Reusing an existing fixture is materially cheaper than building a
        // deterministic scenario from scratch, so make those the first
        // authoring batch even when a new card has one more ability.
        + (!empty($fixtures) ? 20 : 0)
        + min(5, count($mechanics));
    $cards[] = [
        'cardId' => $cardId,
        'name' => function_exists('CardName') ? strval(CardName($cardId) ?? $cardId) : $cardId,
        'type' => function_exists('CardType') ? strval(CardType($cardId) ?? '') : '',
        'abilityCount' => intval($entry['abilityCount'] ?? 1),
        'listenerCount' => intval($entry['listenerCount'] ?? 0),
        'mechanics' => $mechanics,
        'existingFixtures' => $fixtures,
        'semanticFixtures' => $semanticFixtures,
        'priority' => $priority,
    ];
}
usort($cards, fn($a, $b) => $b['priority'] <=> $a['priority'] ?: strcasecmp($a['name'], $b['name']));

$mechanicTotals = [];
foreach ($cards as $card) foreach ($card['mechanics'] as $mechanic) $mechanicTotals[$mechanic] = ($mechanicTotals[$mechanic] ?? 0) + 1;
arsort($mechanicTotals);
$payload = [
    'format' => 'grand-archive-semantic-coverage-backlog/v1',
    'rootName' => $rootName,
    'inventoryExportedAt' => $inventory['exportedAt'] ?? null,
    'generatedAt' => gmdate('c'),
    'summary' => ['implementedCards' => count($cards), 'cardsWithExistingFixtures' => count(array_filter($cards, fn($c) => !empty($c['existingFixtures']))), 'mechanics' => $mechanicTotals],
    'cards' => $cards,
];
if (file_put_contents($outputPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n") === false) {
    throw new RuntimeException("Could not write backlog JSON: {$outputPath}");
}

$lines = [
    '# GrandArchiveSim semantic coverage backlog',
    '',
    "Implemented cards: **" . count($cards) . "**",
    "Cards linked to an existing fixture: **" . $payload['summary']['cardsWithExistingFixtures'] . "**",
    '',
    '## Mechanic groups',
    '',
];
foreach ($mechanicTotals as $mechanic => $count) $lines[] = "- {$mechanic}: {$count}";
$lines[] = '';
$lines[] = "## First {$top} cards to author";
$lines[] = '';
$lines[] = '| Card | Type | Abilities | Mechanics | Existing fixture |';
$lines[] = '| --- | --- | ---: | --- | --- |';
foreach (array_slice($cards, 0, $top) as $card) {
    $fixtures = !empty($card['existingFixtures']) ? implode(', ', $card['existingFixtures']) : '—';
    $name = str_replace('|', '\\|', $card['name']);
    $lines[] = "| {$name} (`{$card['cardId']}`) | {$card['type']} | {$card['abilityCount']} | " . implode(', ', $card['mechanics']) . " | {$fixtures} |";
}
if (file_put_contents($markdownPath, implode("\n", $lines) . "\n") === false) {
    throw new RuntimeException("Could not write backlog Markdown: {$markdownPath}");
}
echo 'Built backlog for ' . count($cards) . " cards.\n";
echo "JSON: {$outputPath}\nMarkdown: {$markdownPath}\n";
