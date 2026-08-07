<?php
// RUN VIA CLI:
//   docker exec -w /var/www/html/TCGEngine otmtcge-swustats-web-server-1 php DevTools/tdd-regression/test_swudeck_setnnn_dictionary.php
//
// SWUDeck's dictionaries are keyed by SET_NNN, matching SWUSim and the deck-JSON interchange
// format. UUIDs remain as a BOUNDARY translation table (UUIDLookup/CardIDLookup) and every
// generated accessor still accepts one, because deck files and stats rows keep their UUIDs until
// the 2026-08-03 migration runs.
//
// Design: docs/superpowers/specs/2026-08-04-swu-shared-card-universe-design.md §3, §4
header('Content-Type: text/plain');
require_once __DIR__ . '/../../SWUDeck/GeneratedCode/GeneratedCardDictionaries.php';

$checks = [];
global $titleData;

// ── The dictionary is SET_NNN-keyed ──────────────────────────────────────────
$keys = array_keys($titleData);
$setNnn = array_filter($keys, fn($k) => preg_match('/^[A-Z0-9]{2,5}_(T\d{2}|\d{2,3})$/', (string)$k));
$checks['every dictionary key is SET_NNN'] = count($setNnn) === count($keys);
$checks['a known card is keyed by SET_NNN'] = ($titleData['SOR_005'] ?? null) === 'Luke Skywalker';

// ── The translation table survives, both directions ──────────────────────────
$checks['UUIDLookup(SET_NNN) -> uuid'] = UUIDLookup('SOR_005') === '2579145458';
$checks['CardIDLookup(uuid) -> SET_NNN'] = CardIDLookup('2579145458') === 'SOR_005';
$checks['round-trips'] = UUIDLookup(CardIDLookup('2579145458')) === '2579145458';

// ── The façade: every accessor takes EITHER key ──────────────────────────────
// This is what lets deck files and stats rows keep their UUIDs until the migration.
foreach ([
    'CardTitle', 'CardSubtitle', 'CardType', 'CardArena', 'CardCost', 'CardHp', 'CardPower',
    'CardAspect', 'CardTrait', 'CardRarity', 'CardSet', 'CardUnique', 'CardText',
    'CardUpgradeHp', 'CardUpgradePower', 'CardCardNumber', 'CardOtherOrientation',
] as $fn) {
    $checks["$fn accepts both keys"] = $fn('SOR_005') === $fn('2579145458');
}
$checks['CardTitle by uuid still resolves'] = CardTitle('2579145458') === 'Luke Skywalker';
$checks['an unknown id returns null, not a fatal'] = CardTitle('NOPE_999') === null;

// ── LeaderUnitLegacyIDByCardID follows the same rule ────────────────────────────
// Renamed from LeaderUnitByUUID on 2026-08-07: it is keyed by CardID (it normalises its argument),
// and it returns a LEGACY identifier for the deployed side — not a card id, and not a filename.
// The old name was wrong on both halves and had already caused a silent empty map.
$checks['LeaderUnitLegacyIDByCardID exists'] = function_exists('LeaderUnitLegacyIDByCardID');
if (function_exists('LeaderUnitLegacyIDByCardID')) {
    $checks['LeaderUnitLegacyIDByCardID accepts both keys'] =
        LeaderUnitLegacyIDByCardID('SOR_005') === LeaderUnitLegacyIDByCardID('2579145458');
    $checks['LeaderUnitLegacyIDByCardID resolves a legacy id'] = LeaderUnitLegacyIDByCardID('SOR_005') === '0dcb77795c';
    $checks['a non-leader resolves to null'] = LeaderUnitLegacyIDByCardID('SOR_033') === null;
}

// The value must NOT be treated as art. The corpus is entirely SET_NNN-named, so a legacy id names
// no file; building a path from one would resurrect the third key space the migration removed.
$legacy = function_exists('LeaderUnitLegacyIDByCardID') ? LeaderUnitLegacyIDByCardID('SOR_005') : '';
$corpus = __DIR__ . '/../../AppCore/SWU/Images';
$checks['the legacy id names no file'] =
    $legacy !== '' && glob("$corpus/*/{$legacy}*") === [];
$checks['the SET_NNN back art DOES exist'] = file_exists("$corpus/crops/SOR_005_back_cropped.png");

// The consumer that matters: this map is what keeps ~2,984 prod stat rows keyed by a leader's
// FLIPPED-side asset id (ad86d54e97 -> TWI_017) attached to that leader. It fails SILENTLY to an
// empty array when the underlying accessor is missing — which is exactly what a rename could cause
// if the dictionary were not regenerated. Assert it is populated, not merely that it returns.
require_once __DIR__ . '/../../AppCore/SWU/CardIdentity.php';
$leaderUnitMap = SWUCardIdentityLeaderUnitMap();
$checks['leader-unit legacy-id map is NOT empty'] = count($leaderUnitMap) > 100;
$checks['the Palpatine flip asset still maps'] = ($leaderUnitMap['ad86d54e97'] ?? null) === 'TWI_017';

// ── The catalog is unchanged in SIZE, only in shape ──────────────────────────
$all = GetAllCardIds();
$checks['GetAllCardIds returns SET_NNN'] =
    count($all) > 0 && preg_match('/^[A-Z0-9]{2,5}_(T\d{2}|\d{2,3})$/', (string)$all[0]) === 1;
// These started life as flat "nothing was lost in the re-key" constants. Preview cards now merge
// into SWUDeck too, so the totals move whenever the mock source does — derive the mock delta and
// assert the OFFICIAL remainder, which is the number the re-key could actually have damaged.
// A SET_NNN collision (two printings onto one key) shows up here as a shortfall.
$mockCount = 0; $mockTokenCount = 0;
if (file_exists(__DIR__ . '/../../AppCore/SWU/MockCardMerge.php')) {
    require_once __DIR__ . '/../../AppCore/SWU/MockCardMerge.php';
    $mockIDs = array_keys(SWULoadMockCards());
    $mockCount = count($mockIDs);
    // Tokens are not deckbuildable, so they merge into the dictionary but NOT the browse catalog.
    $mockTokenCount = count(array_filter($mockIDs, fn($i) => preg_match('/_T\d{2}$/', (string)$i)));
}
// Official tokens JOINED the dictionary on 2026-08-05 (spec §4) so their ~48k stat rows resolve:
// 2278 -> 2338. They must NOT join the browse catalog, which is why the catalog moved the other
// way (2179 -> 2212 counts only non-token official cards).
//
// Assert the RELATIONSHIP, not just the magic number: the catalog is the dictionary minus tokens
// minus the cards no printing admits, so a SET_NNN collision shows up as the official card count
// dropping while the token count stays put.
$officialCards  = count($titleData) - $mockCount;
$officialAll    = count($all) - ($mockCount - $mockTokenCount);
$dictTokens     = count(array_filter(array_keys($titleData), fn($i) => preg_match('/_T\d{2}$/', (string)$i)));
$catalogTokens  = count(array_filter($all, fn($i) => preg_match('/_T\d{2}$/', (string)$i)));

// 2278 non-token official cards + 24 official tokens. Verified there is no SET_NNN collision:
// 2278 + 24 + 36 mocks == 2338 titleData keys exactly, so nothing overwrote anything.
$checks['official card count (2302, post token inclusion)'] = $officialCards === 2302;
// 2179 -> 2178 across the token re-fetch. Confirmed NOT caused by a token claiming a real card's
// title+subtitle hash (checked: 10 hashes are token-claimed, 0 real cards suppressed).
$checks['official catalog count (2178)'] = $officialAll === 2178;
$checks['tokens ARE in the dictionary'] = $dictTokens >= 20;
$checks['tokens are NOT in the catalog'] = $catalogTokens === 0;
$checks['catalog is strictly smaller than the dictionary'] = $officialAll < $officialCards;
$checks['every mock reached the dictionary'] = $mockCount === 0 || CardTitle(array_key_first(SWULoadMockCards())) !== null;

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
