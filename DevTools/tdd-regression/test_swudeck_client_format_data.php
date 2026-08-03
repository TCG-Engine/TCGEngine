<?php
// http://localhost:3100/TCGEngine/DevTools/tdd-regression/test_swudeck_client_format_data.php
header('Content-Type: text/plain');
include_once __DIR__ . '/../../SWUDeck/GeneratedCode/GeneratedCardDictionaries.php'; // $titleData, UUIDLookup
include_once __DIR__ . '/../../SWUDeck/Custom/DeckFormats.php';

$checks = [];

// Premier: fixed curated set list, no bans.
$premier = SWUDeckClientFormatData('premier');
$checks['premier legalSets matches config'] = $premier['legalSets'] === ['JTL', 'LOF', 'SEC', 'IBH', 'LAW', 'ASH'];
$checks['premier has no banned UUIDs'] = $premier['bannedUUIDs'] === [];

// Eternal: every set legal, JTL_140 + JTL_170 banned — both must resolve to a UUID.
$eternal = SWUDeckClientFormatData('eternal');
$checks['eternal legalSets includes SOR'] = in_array('SOR', $eternal['legalSets'], true);
$checks['eternal legalSets includes ASH'] = in_array('ASH', $eternal['legalSets'], true);
$checks['eternal has exactly 2 banned UUIDs'] = count($eternal['bannedUUIDs']) === 2;
$expectedBannedUUIDs = [UUIDLookup('JTL_140'), UUIDLookup('JTL_170')];
sort($expectedBannedUUIDs);
$actualBannedUUIDs = $eternal['bannedUUIDs'];
sort($actualBannedUUIDs);
$checks['eternal banned UUIDs match JTL_140/JTL_170'] = $actualBannedUUIDs === $expectedBannedUUIDs;

// Open and Twin Suns: no bans configured today.
$open = SWUDeckClientFormatData('open');
$checks['open has no banned UUIDs'] = $open['bannedUUIDs'] === [];
$twinsuns = SWUDeckClientFormatData('twinsuns');
$checks['twinsuns has no banned UUIDs'] = $twinsuns['bannedUUIDs'] === [];

// Unknown format: degrades to empty/safe output, doesn't throw.
$unknown = SWUDeckClientFormatData('nonsense');
$checks['unknown format returns empty legalSets'] = $unknown['legalSets'] === [];
$checks['unknown format returns empty bannedUUIDs'] = $unknown['bannedUUIDs'] === [];

// ── PADAWAN client payload ───────────────────────────────────────────────────
// The client CANNOT re-derive this rule: cardReprintSets exposes reprint SET CODES only, not
// per-printing rarity, so a client-side rarity check would wrongly hide the SOR printing of
// Prepare for Takeoff. The list is therefore computed server-side from the same PHP predicate.
$padawan = SWUDeckClientFormatData('padawan');
$checks['padawan ships a rarity allowlist'] = is_array($padawan['rarityLegalUUIDs']);
$checks['padawan allowlist is substantial']  = count($padawan['rarityLegalUUIDs'] ?? []) > 900;

$inList = fn($setID) => in_array(UUIDLookup($setID), $padawan['rarityLegalUUIDs'] ?? [], true);
$checks['allowlist has a common']            = $inList('SOR_033');
$checks['allowlist has a common base']       = $inList('JTL_023');
$checks['allowlist omits a special']         = !$inList('SOR_236');
$checks['allowlist omits a rare']            = !$inList('JTL_140');
$checks['allowlist omits an uncommon']       = !$inList('JTL_170');
$checks['allowlist omits a rare base (ECL)'] = !$inList('SOR_022');
// LEADERS ARE EXEMPT — a Rare leader and an IBH Special leader must both be browsable.
$checks['allowlist has a rare leader']       = $inList('JTL_001');
$checks['allowlist has an IBH leader']       = $inList('IBH_001');
$checks['allowlist omits an IBH card']       = !$inList('IBH_003');
// REPRINT GROUP — both printings of Prepare for Takeoff must be browsable.
$checks['allowlist has PfT common printing']   = $inList('JTL_128');
$checks['allowlist has PfT uncommon printing'] = $inList('SOR_125');
$checks['allowlist has the special reprint']   = $inList('SHD_030');

// NO REGRESSION: formats without a rarity rule ship null, so their payload is byte-identical.
foreach (['premier', 'eternal', 'twinsuns', 'open'] as $f) {
    $checks["$f ships null rarity allowlist"] = SWUDeckClientFormatData($f)['rarityLegalUUIDs'] === null;
}
$checks['unknown format ships null rarity allowlist'] =
    SWUDeckClientFormatData('nonsense')['rarityLegalUUIDs'] === null;

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
