<?php
// The deck view's per-card stat overlay must answer for WHICHEVER PRINTING IS DISPLAYED.
// Reported 2026-09-01: Viper Probe Droid showed "No Data" in a deck whose other cards all had stats.
// Cause: GetNextTurn renders the LATEST printing (SEC_239) while carddeckstats stores the EARLIEST
// (SOR_228) — the two halves of CardDisplayID.php's own documented invariant — and the generated
// lookup was keyed on the stored id alone.
$root = realpath(__DIR__ . '/../..');
require_once $root . '/SWUDeck/GeneratedCode/GeneratedCardDictionaries.php';
require_once $root . '/SWUDeck/Custom/DeckFormats.php';
require_once $root . '/AppCore/SWU/CardDisplayID.php';
require_once $root . '/AppCore/SWU/CardStatHeatmap.php';
SWUDeckSetReprintUniverse();

$checks = [];

// 1) THE REPORTED BUG. A row stored under the canonical printing must be readable under the printing
//    the deck actually renders.
$h = SWUBuildCardStatHeatmaps([
    ['cardID' => 'SOR_228', 'timesPlayed' => 4, 'timesPlayedInWins' => 3, 'timesResourced' => 1],
]);
$display = SWUDisplayCardID('SOR_228');
$checks['the DISPLAYED printing resolves (the reported bug)'] = isset($h['play'][$display]);
$checks['both printings report the SAME rate'] =
    ($h['play']['SOR_228'] ?? null) === ($h['play']['SEC_239'] ?? null)
    && ($h['play']['SOR_228'] ?? null) === 0.75;

// 2) Rows under SEVERAL printings are ONE card's history and must SUM, not last-wins. Summed:
//    5 wins / 8 plays = 0.625. Last-wins would read 0.5 (or 1.0) and averaging the two rates 0.75.
$h2 = SWUBuildCardStatHeatmaps([
    ['cardID' => 'SOR_228', 'timesPlayed' => 4, 'timesPlayedInWins' => 2, 'timesResourced' => 0],
    ['cardID' => 'SEC_239', 'timesPlayed' => 4, 'timesPlayedInWins' => 3, 'timesResourced' => 0],
]);
$checks['rows across printings AGGREGATE'] = ($h2['play']['SEC_239'] ?? null) === 0.625;

// 3) A card that was never reprinted still works, and is unaffected by the expansion.
$h3 = SWUBuildCardStatHeatmaps([
    ['cardID' => 'SOR_095', 'timesPlayed' => 2, 'timesPlayedInWins' => 1, 'timesResourced' => 2],
]);
$checks['a non-reprinted card is unchanged'] = ($h3['play']['SOR_095'] ?? null) === 0.5;
$checks['resource rate uses played+resourced as the denominator'] = ($h3['resource']['SOR_095'] ?? null) === 0.5;

// 4) Zero plays is "no data" (-1), not a divide-by-zero or a 0% that reads as a real losing card.
$h4 = SWUBuildCardStatHeatmaps([
    ['cardID' => 'SOR_228', 'timesPlayed' => 0, 'timesPlayedInWins' => 0, 'timesResourced' => 0],
]);
$checks['zero plays reports -1, not 0'] = ($h4['play']['SEC_239'] ?? null) === -1;

// 5) The expansion must not invent entries for cards with no rows at all.
$checks['a card with no row stays absent'] = !isset($h['play']['SOR_095']);

// 6) The stats table still holds FFG-UUID keys wherever the 2026-08-04 re-key has not been run (the
//    local dev clone is entirely UUID-keyed), and external clients still SEND UUIDs. A UUID row must
//    resolve to the SET_NNN the deck renders, or the overlay is blank for every card on such a DB.
//    2703877689 is Resupply, canonical SOR_126, displayed as TWI_127.
$h5 = SWUBuildCardStatHeatmaps([
    ['cardID' => '2703877689', 'timesPlayed' => 2, 'timesPlayedInWins' => 1, 'timesResourced' => 0],
]);
$checks['a UUID-keyed row resolves to its SET_NNN printings'] =
    ($h5['play']['SOR_126'] ?? null) === 0.5 && ($h5['play']['TWI_127'] ?? null) === 0.5;

// 7) A card's UUID-era and SET_NNN-era rows are ONE history and must merge, not overwrite.
//    2+2 plays, 1+2 wins = 0.75.
$h6 = SWUBuildCardStatHeatmaps([
    ['cardID' => '2703877689', 'timesPlayed' => 2, 'timesPlayedInWins' => 1, 'timesResourced' => 0],
    ['cardID' => 'TWI_127',    'timesPlayed' => 2, 'timesPlayedInWins' => 2, 'timesResourced' => 0],
]);
$checks['UUID-era and SET_NNN-era rows merge'] = ($h6['play']['SOR_126'] ?? null) === 0.75;

// 8) An unresolvable value must not be dropped silently NOR collapsed with others.
$h7 = SWUBuildCardStatHeatmaps([
    ['cardID' => 'notacard', 'timesPlayed' => 2, 'timesPlayedInWins' => 1, 'timesResourced' => 0],
]);
$checks['an unresolvable id keeps its own key'] = ($h7['play']['notacard'] ?? null) === 0.5;

$fail = array_keys(array_filter($checks, fn($v) => !$v));
if ($fail) { fwrite(STDERR, "FAIL (" . count($fail) . "/" . count($checks) . "):\n  - " . implode("\n  - ", $fail) . "\n"); exit(1); }
echo "PASS (" . count($checks) . " checks)\n";
