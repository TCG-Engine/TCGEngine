<?php
// RUN VIA CLI:
//   docker exec otmtcge-swusim-web-server-1 php -d xdebug.mode=off /var/www/html/TCGEngine/DevTools/tdd-regression/test_swudeck_rarity_universe.php
//
// Guards the highest-risk seam in the Padawan feature: SWUCardRarity() must resolve for a SET_NNN
// id on the SWUDeck side, or the fail-closed predicate below rejects every card.
//
// Until 2026-08-05 SWUDeck's $rarityData was keyed by INT UUID while the shared validator spoke
// SET_NNN, and SWUDeckSetReprintUniverse() existed largely to bridge that. The dictionary is now
// SET_NNN-keyed on both sides, so the bridge is an identity — but the universe it publishes is
// still what SWUCardRarity() reads, so this test guards exactly as much as it did before.
//
// Because SWUCardHasLegalRarityPrint() fails CLOSED on missing rarity, the resulting breakage is
// loud rather than silent: every card — and the base — is rejected, so no Padawan deck can ever be
// saved as legal in the builder. (Had it failed OPEN, the same seam would instead have passed a
// deck of 50 Rares with no error at all. Fail-closed is what makes this detectable.)
// The load-bearing check below is therefore "a legal all-Common deck IS legal", not the Rare deck.
header('Content-Type: text/plain');
include_once __DIR__ . '/../../SWUDeck/GeneratedCode/GeneratedCardDictionaries.php';
include_once __DIR__ . '/../../SWUDeck/Custom/DeckFormats.php';

$checks = [];

// Precondition: SWUDeck's raw dictionary is SET_NNN-keyed with single-letter values.
global $rarityData;
$checks['precondition: SWUDeck rarityData is SET_NNN-keyed'] = isset($rarityData['SOR_033']);
$checks['precondition: SWUDeck uses letter codes']           = ($rarityData['SOR_033'] ?? null) === 'C';
// ...and the pre-migration UUID key is genuinely gone, so a stale reader fails loudly.
$checks['precondition: the old UUID key is absent'] = !isset($rarityData[(int)UUIDLookup('SOR_033')]);

SWUDeckSetReprintUniverse();

$checks['rarity universe published'] = is_array($GLOBALS['SWURarityUniverse'] ?? null);
$checks['rarity universe is non-trivial'] = count($GLOBALS['SWURarityUniverse'] ?? []) > 2000;
$checks['reprint universe still published'] = is_array($GLOBALS['SWUReprintUniverse'] ?? null);

// The whole point: SET_NNN lookups now resolve.
$checks['SET_NNN common resolves']  = _SWURarityCode(SWUCardRarity('SOR_033')) === 'C';
$checks['SET_NNN special resolves'] = _SWURarityCode(SWUCardRarity('SOR_236')) === 'S';
$checks['SET_NNN rare resolves']    = _SWURarityCode(SWUCardRarity('JTL_140')) === 'R';

// End-to-end through the shared predicate, on SWUDeck's dictionary.
$ETERNAL = SWUFormatLegalSets('eternal');
$C = ['Common'];
$checks['deck-side: common passes']        = SWUCardHasLegalRarityPrint('SOR_033', $ETERNAL, $C) === true;
$checks['deck-side: special fails']        = SWUCardHasLegalRarityPrint('SOR_236', $ETERNAL, $C) === false;
$checks['deck-side: rare fails']           = SWUCardHasLegalRarityPrint('JTL_140', $ETERNAL, $C) === false;
$checks['deck-side: reprint group works']  = SWUCardHasLegalRarityPrint('SHD_030', $ETERNAL, $C) === true;
$checks['deck-side: downshift works']      = SWUCardHasLegalRarityPrint('SOR_125', $ETERNAL, $C) === true;

// THE REGRESSION THAT MATTERS: with the rekey missing, rarity is null for every card and the
// fail-closed predicate rejects EVERYTHING — so a perfectly legal all-Common deck is refused.
// This check, not the Rare one below, is what actually breaks when this seam regresses.
function _deckSidePadawanMain() {
    $trip = ['JTL_033','JTL_034','JTL_040','JTL_044','JTL_051','JTL_052','JTL_058','JTL_060',
             'JTL_061','JTL_063','JTL_064','JTL_065','JTL_067','JTL_068','JTL_069','JTL_071'];
    $deck = [];
    foreach ($trip as $c) { $deck[] = $c; $deck[] = $c; $deck[] = $c; }  // 48
    $deck[] = 'JTL_076'; $deck[] = 'JTL_078';                            // 50
    return $deck;
}
$legalVerdict = SWUCheckFormat('padawan', 'JTL_001', 'JTL_023', _deckSidePadawanMain(), []);
$checks['deck-side: a legal all-common deck IS legal'] = $legalVerdict === [];
$checks['deck-side: common base accepted'] = !in_array(
    'Base JTL_023 must have a Common printing in padawan.', $legalVerdict, true);

// And the converse still holds: a Rare-stuffed deck is rejected.
$rareDeck = array_fill(0, 50, 'JTL_140');
$checks['deck-side: 50 rares is NOT legal'] = !empty(
    SWUCheckFormat('padawan', 'JTL_001', 'JTL_023', $rareDeck, []));

// Idempotent — the editor polls validation on every deck mutation.
$before = count($GLOBALS['SWURarityUniverse'] ?? []);
SWUDeckSetReprintUniverse();
$checks['idempotent'] = count($GLOBALS['SWURarityUniverse'] ?? []) === $before && $before > 0;

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
