<?php
// READ-ONLY guard for GrandArchiveSim card dictionary integrity. No database, no POST.
//
// The card corpus flows: api.gatcg.com -> zzCardCodeGenerator -> cardArrayCache.json -> the generated
// GeneratedCardDictionaries.php. A break anywhere in that chain — a spoiler import that drops a stat,
// a dictionary that stops resolving an id, a typo'd element — lands silently because GeneratedCode is
// gitignored and the game reads the generated functions, not the source. This guard pins the generated
// output against the source cache so the failure happens HERE, by card id, instead of as a "why did
// card X lose its power" report months later.
//
// Type-aware on purpose: a blanket "every card needs power/life/level" rule would reject actions,
// items, and tokens. Requirements are instead keyed off the card's real type:
//   champion -> level + life     ally -> power + life     weapon -> durability
//
//   php DevTools/tdd-regression/test_grand_archive_dictionary_integrity.php
error_reporting(E_ALL & ~E_DEPRECATED);
chdir(dirname(dirname(__DIR__)));
require_once './GrandArchiveSim/GeneratedCode/GeneratedCardDictionaries.php';

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

// ── source of truth: the cache the dictionaries are generated FROM ──────────
$cachePath = './GrandArchiveSim/GeneratedCode/cardArrayCache.json';
if (!is_file($cachePath)) { echo "FAIL: missing $cachePath\n"; exit(1); }
$cache = json_decode((string)file_get_contents($cachePath), true);
$cards = is_array($cache['cardArray'] ?? null) ? $cache['cardArray'] : [];

// GA element and class vocabularies are core game constants; adding one is a real game change that
// should trip this guard (same philosophy as the token-catalog and format-list drift guards).
const GA_ELEMENTS = ['NORM', 'FIRE', 'WATER', 'WIND', 'TERA', 'CRUX', 'UMBRA', 'EXIA', 'LUXEM', 'ASTRA', 'ARCANE', 'NEOS', 'EXALTED'];
const GA_CLASSES  = ['WARRIOR', 'CLERIC', 'MAGE', 'SPIRIT', 'RANGER', 'ASSASSIN', 'TAMER', 'GUARDIAN', 'ANOMALY'];
const GA_REAL_TYPES = ['CHAMPION', 'ALLY', 'ACTION', 'ITEM', 'WEAPON', 'REGALIA', 'DOMAIN', 'PHANTASIA', 'MASTERY', 'ATTACK', 'STATUS', 'GREATER BOON', 'LESSER BOON'];

// The valid set codes are derived from the cache's own edition prefixes, NOT a hard-coded list, so a
// newly released set validates without editing this guard.
$validPrefixes = [];
$cacheById = [];
foreach ($cards as $card) {
    $cacheById[(string)($card['id'] ?? '')] = $card;
    foreach (($card['editions'] ?? []) as $ed) {
        $prefix = (string)($ed['set']['prefix'] ?? '');
        if ($prefix !== '') $validPrefixes[$prefix] = true;
    }
}

// A card's single "real" type among the modifier tokens (UNIQUE, TOKEN) and the gameplay types.
function gaRealType(array $card): ?string {
    foreach ((array)($card['types'] ?? []) as $t) {
        $t = strtoupper(trim((string)$t));
        if (in_array($t, GA_REAL_TYPES, true)) return $t;
    }
    return null;
}

// Orientation variants (double-faced cards) are extra generated ids not present in cardArray; resolve
// them back to their cache card so the structured checks still apply.
function gaCacheCard(string $id, array $cacheById): ?array {
    if (isset($cacheById[$id])) return $cacheById[$id];
    $parent = CardOtherOrientation($id);
    return ($parent !== null && isset($cacheById[$parent])) ? $cacheById[$parent] : null;
}

// ── 1. ids are unique and resolve ───────────────────────────────────────────
$ids = GetAllCardIds();
$check(count($ids) === count(array_unique($ids)), 'GetAllCardIds() emits unique ids (' . count($ids) . ' cards)');

$numericGetters = [
    'memory' => 'CardCost_memory', 'reserve' => 'CardCost_reserve',
    'level' => 'CardLevel', 'power' => 'CardPower', 'life' => 'CardLife', 'durability' => 'CardDurability',
];

$missingName = $missingType = $missingSet = $badNumeric = $badSet = [];
$badTypeStat = [];
$badElement = $badClass = $badSubtype = [];

foreach ($ids as $id) {
    // name / type / set presence + resolution
    $name = CardName($id);
    $type = CardType($id);
    $set  = CardSet($id);
    if (!is_string($name) || trim($name) === '') $missingName[] = $id;
    if (!is_string($type) || trim($type) === '') $missingType[] = $id;
    if (!is_string($set) || trim($set) === '') $missingSet[] = $id;
    if (is_string($set) && $set !== '' && !isset($validPrefixes[$set])) $badSet[] = "$id => $set";

    // numeric fields are integers when supplied (null means "does not apply")
    foreach ($numericGetters as $label => $getter) {
        $value = $getter($id);
        if ($value !== null && !is_int($value)) $badNumeric[] = "$id $label = " . var_export($value, true);
    }

    // structured checks against the source cache
    $card = gaCacheCard((string)$id, $cacheById);
    if ($card === null) { $missingName[] = $id . ' (no cache card)'; continue; }

    $kind = gaRealType($card);
    if ($kind === 'CHAMPION') {
        if (!is_int($card['level'] ?? null) || !is_int($card['life'] ?? null)) $badTypeStat[] = "$id champion level/life";
    } elseif ($kind === 'ALLY') {
        if (!is_int($card['power'] ?? null) || !is_int($card['life'] ?? null)) $badTypeStat[] = "$id ally power/life";
    } elseif ($kind === 'WEAPON') {
        if (!is_int($card['durability'] ?? null)) $badTypeStat[] = "$id weapon durability";
    }

    foreach ((array)($card['elements'] ?? []) as $e) {
        if (!in_array(strtoupper(trim((string)$e)), GA_ELEMENTS, true)) $badElement[] = "$id => $e";
    }
    foreach ((array)($card['classes'] ?? []) as $c) {
        if (!in_array(strtoupper(trim((string)$c)), GA_CLASSES, true)) $badClass[] = "$id => $c";
    }
    foreach ((array)($card['subtypes'] ?? []) as $s) {
        $s = (string)$s;
        if (trim($s) === '' || $s !== strtoupper($s) || preg_match('/\s/', $s)) $badSubtype[] = "$id => $s";
    }
}

$check($missingName === [], 'every card has a non-empty name' . ($missingName ? ' (missing: ' . implode(', ', array_slice($missingName, 0, 10)) . ')' : ''));
$check($missingType === [], 'every card has a non-empty type' . ($missingType ? ' (missing: ' . implode(', ', array_slice($missingType, 0, 10)) . ')' : ''));
$check($missingSet === [], 'every card has a non-empty set' . ($missingSet ? ' (missing: ' . implode(', ', array_slice($missingSet, 0, 10)) . ')' : ''));
$check($badSet === [], 'every set resolves to a cache edition prefix' . ($badSet ? ' (bad: ' . implode(', ', array_slice($badSet, 0, 10)) . ')' : ''));
$check($badNumeric === [], 'numeric fields are integers when supplied' . ($badNumeric ? ' (bad: ' . implode(', ', array_slice($badNumeric, 0, 10)) . ')' : ''));
$check($badTypeStat === [], 'type-aware stat requirements hold (champion lvl/life, ally pow/life, weapon durability)' . ($badTypeStat ? ' (bad: ' . implode(', ', array_slice($badTypeStat, 0, 10)) . ')' : ''));
$check($badElement === [], 'every element is in the known GA vocabulary' . ($badElement ? ' (bad: ' . implode(', ', array_slice($badElement, 0, 10)) . ')' : ''));
$check($badClass === [], 'every class is in the known GA vocabulary' . ($badClass ? ' (bad: ' . implode(', ', array_slice($badClass, 0, 10)) . ')' : ''));
$check($badSubtype === [], 'every subtype is normalized (non-empty, uppercase, no whitespace)' . ($badSubtype ? ' (bad: ' . implode(', ', array_slice($badSubtype, 0, 10)) . ')' : ''));

if ($fails === 0) echo "\nALL PASS\n";
else echo "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
