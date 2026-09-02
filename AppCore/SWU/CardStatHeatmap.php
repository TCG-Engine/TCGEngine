<?php
// The per-card stat heatmap the deck view overlays on each card (play win rate / resource rate).
//
// ⚠ WHY THIS EXISTS RATHER THAN A STRAIGHT LOOP OVER THE STATS ROWS: display and storage deliberately
// use DIFFERENT PRINTINGS. CardDisplayID.php's invariant is
//     display is always the latest printing; write is always the earliest
// so SWUDeck/GetNextTurn.php renders Viper Probe Droid as SEC_239 while its carddeckstats row is
// stored under SOR_228. A lookup keyed on the stored id alone therefore falls through to "No Data"
// for EVERY reprinted card — reported 2026-09-01 with exactly that card.
//
// Two things are done here, and the second depends on the first:
//   1. AGGREGATE by canonical id. One deck can hold rows under SEVERAL printings (games logged either
//      side of a reprint), and those are one card's history — summing is the only correct reading.
//      It is also what makes expansion safe: without it, two rows in the same reprint group would each
//      expand over the whole group and emit DUPLICATE keys, where one silently wins and the other
//      becomes dead data.
//   2. EXPAND each canonical result across its whole reprint group, so the lookup answers for whichever
//      printing the renderer happens to show.
//
// Rates are computed from the SUMMED counters, never averaged from per-printing rates — averaging
// rates would weight a 1-play printing the same as a 200-play one.
require_once __DIR__ . '/Overrides.php';        // CardIDOverride  (any printing -> EARLIEST)
require_once __DIR__ . '/DeckValidation.php';   // SWUReprintGroup (canonical -> every printing)
require_once __DIR__ . '/CardIdentity.php';     // SWUCardIdentityToStored (UUID or SET_NNN -> canonical)

// $rows: carddeckstats rows (each needs cardID, timesPlayed, timesPlayedInWins, timesResourced).
// Returns ['play' => [cardID => rate|-1], 'resource' => [cardID => rate|-1]] covering every printing
// of every card that has a row. -1 means "no data" and is what the overlay renders as such.
function SWUBuildCardStatHeatmaps(array $rows): array
{
    $agg = [];
    foreach ($rows as $row) {
        $cardID = (string)($row['cardID'] ?? '');
        if ($cardID === '') continue;
        // SWUCardIdentityToStored is the project's one canonicaliser and folds BOTH shapes that are in
        // circulation: an FFG UUID (the pre-2026-08-04 key, and still the wire format external clients
        // send) and a SET_NNN reprint, either way to the EARLIEST printing. Using it rather than a bare
        // CardIDOverride also merges a card's UUID-era and SET_NNN-era history, which is the same
        // "stats aggregate under ONE identity forever" rule the ingress path already follows.
        // Unresolvable values keep their own key rather than being dropped or collapsed together.
        $canon = SWUCardIdentityToStored($cardID) ?? $cardID;
        if (!isset($agg[$canon])) $agg[$canon] = ['played' => 0, 'playedInWins' => 0, 'resourced' => 0];
        $agg[$canon]['played']       += (int)($row['timesPlayed'] ?? 0);
        $agg[$canon]['playedInWins'] += (int)($row['timesPlayedInWins'] ?? 0);
        $agg[$canon]['resourced']    += (int)($row['timesResourced'] ?? 0);
    }

    $play = [];
    $resource = [];
    foreach ($agg as $canon => $v) {
        $playRate = $v['played'] > 0 ? round($v['playedInWins'] / $v['played'], 4) : -1;
        $seen     = $v['resourced'] + $v['played'];
        $resRate  = $seen > 0 ? round($v['resourced'] / $seen, 4) : -1;
        // SWUReprintGroup always includes the canonical id itself, so a card that was never reprinted
        // still gets exactly one entry and this is a no-op for it.
        foreach (SWUReprintGroup($canon) as $printing) {
            $play[$printing]     = $playRate;
            $resource[$printing] = $resRate;
        }
    }
    return ['play' => $play, 'resource' => $resource];
}
