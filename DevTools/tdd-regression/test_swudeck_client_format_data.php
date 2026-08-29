<?php
// http://localhost:3100/TCGEngine/DevTools/tdd-regression/test_swudeck_client_format_data.php
header('Content-Type: text/plain');
include_once __DIR__ . '/../../SWUDeck/GeneratedCode/GeneratedCardDictionaries.php'; // $titleData, UUIDLookup
include_once __DIR__ . '/../../SWUDeck/Custom/DeckFormats.php';

$checks = [];

// Premier: fixed curated set list, and the bans the config actually declares.
// ⚠ This used to assert "no bans", written when Premier had none. ASH_011 was banned deliberately
// (commit 64fed276, "updated format bans") and the test simply never followed — so it sat red while
// the code was right. Assert AGAINST THE CONFIG rather than a hardcoded list, so the next ban does
// not re-break it; the eternal block below still pins its exact ids, which is what catches a payload
// that silently drops bans on the way to the client.
$premier = SWUDeckClientFormatData('premier');
$checks['premier legalSets matches config'] = $premier['legalSets'] === ['JTL', 'LOF', 'SEC', 'IBH', 'LAW', 'ASH'];
$premierConfigBans = SWUGetFormat('premier')['banned'];
sort($premierConfigBans);
$premierPayloadBans = $premier['bannedIDs'];
sort($premierPayloadBans);
$checks['premier banned IDs match the format config'] = $premierPayloadBans === $premierConfigBans;
$checks['premier bans are non-empty (ASH_011 today)'] = in_array('ASH_011', $premierPayloadBans, true);

// Eternal: every set legal, JTL_140 + JTL_170 banned.
// Since 2026-08-04 the payload carries SET_NNN ids directly — no UUIDLookup round-trip.
$eternal = SWUDeckClientFormatData('eternal');
$checks['eternal legalSets includes SOR'] = in_array('SOR', $eternal['legalSets'], true);
$checks['eternal legalSets includes ASH'] = in_array('ASH', $eternal['legalSets'], true);
$checks['eternal has exactly 2 banned IDs'] = count($eternal['bannedIDs']) === 2;
$expectedBannedIDs = ['JTL_140', 'JTL_170'];
sort($expectedBannedIDs);
$actualBannedIDs = $eternal['bannedIDs'];
sort($actualBannedIDs);
$checks['eternal banned IDs match JTL_140/JTL_170'] = $actualBannedIDs === $expectedBannedIDs;

// Open and Twin Suns: no bans configured today.
$open = SWUDeckClientFormatData('open');
$checks['open has no banned UUIDs'] = $open['bannedIDs'] === [];
$twinsuns = SWUDeckClientFormatData('twinsuns');
$checks['twinsuns has no banned UUIDs'] = $twinsuns['bannedIDs'] === [];

// Unknown format: degrades to empty/safe output, doesn't throw.
$unknown = SWUDeckClientFormatData('nonsense');
$checks['unknown format returns empty legalSets'] = $unknown['legalSets'] === [];
$checks['unknown format returns empty bannedIDs'] = $unknown['bannedIDs'] === [];

// ── PADAWAN client payload ───────────────────────────────────────────────────
// The client CANNOT re-derive this rule: cardReprintSets exposes reprint SET CODES only, not
// per-printing rarity, so a client-side rarity check would wrongly hide the SOR printing of
// Prepare for Takeoff. The list is therefore computed server-side from the same PHP predicate.
$padawan = SWUDeckClientFormatData('padawan');
$checks['padawan ships a rarity allowlist'] = is_array($padawan['rarityLegalIDs']);
$checks['padawan allowlist is substantial']  = count($padawan['rarityLegalIDs'] ?? []) > 900;

// The allowlist holds SET_NNN ids directly now, so membership is a straight comparison.
$inList = fn($setID) => in_array($setID, $padawan['rarityLegalIDs'] ?? [], true);
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
    $checks["$f ships null rarity allowlist"] = SWUDeckClientFormatData($f)['rarityLegalIDs'] === null;
}
$checks['unknown format ships null rarity allowlist'] =
    SWUDeckClientFormatData('nonsense')['rarityLegalIDs'] === null;

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
