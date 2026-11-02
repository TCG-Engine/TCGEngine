<?php
// The DISPLAY direction of card identity: any printing -> the latest printing we should SHOW.
//
// Exactly the inverse of CardIDOverride() in Overrides.php, which folds any printing to the EARLIEST
// and remains the only write-side normaliser. The invariant that keeps the two safe:
//
//     display is always the latest printing; write is always the earliest
//
// Never compose them in the same direction. CardIDOverride(SWUDisplayCardID($x)) is a round trip back
// to canonical and is fine (the stats path relies on exactly that); applying both as if they stacked
// is always a bug.
//
// Special (rarity 'S') printings are excluded on purpose. They are showcase/hyperspace variants, and a
// literal "newest printing" would silently swap 14 cards to showcase art in every deck — Cell Block
// Guard, Open Fire, Waylay, General Tagge and friends.
require_once __DIR__ . '/Overrides.php';        // CardIDOverride
require_once __DIR__ . '/DeckValidation.php';   // SWUCardSet (and the reprint machinery)

//
// $releasedOnly also skips printings from a set that is still PREVIEWING (AppCore/SWU/PreviewSets.php). The deck export
// asks for it: other sites import those decks and do not have a preview set's cards yet (owner, 2026-10-26 — the IC27
// reprints broke imports). A card that exists only in the preview set keeps its own id. Removing the set from
// PreviewSets.php on release day lifts the hold.
function SWUDisplayCardID(string $cardID, bool $releasedOnly = false): string {
    static $maps = [];
    if ($cardID === '') return $cardID;
    $mode = $releasedOnly ? 'released' : 'all';
    if (!isset($maps[$mode])) {
        $order = @include __DIR__ . '/AllSets.php';
        if (!is_array($order)) $order = [];
        $held = [];
        if ($releasedOnly) {
            $preview = @include __DIR__ . '/PreviewSets.php';
            foreach ((is_array($preview) ? $preview : []) as $set) $held[strtoupper((string)$set)] = true;
        }
        // The same universe SWUReprintGroup uses: SWUDeck publishes SWUReprintUniverse (its dictionary
        // needs an explicit SET_NNN list); SET_NNN-keyed apps fall back to the dictionary's own keys.
        $ids = $GLOBALS['SWUReprintUniverse'] ?? null;
        if (!is_array($ids)) {
            global $titleData;
            $ids = is_array($titleData) ? array_keys($titleData) : [];
        }
        $groups = [];
        foreach ($ids as $id) $groups[CardIDOverride($id)][] = $id;
        $map = [];
        foreach ($groups as $canon => $printings) {
            $best = null;
            $bestOrder = -1;
            foreach ($printings as $p) {
                if (strtoupper((string)CardRarity($p)) === 'S') continue;   // showcase variant
                if (isset($held[SWUCardSet($p)])) continue;                 // previewing set ($releasedOnly)
                $o = $order[SWUCardSet($p)] ?? -1;
                if ($o > $bestOrder) { $bestOrder = $o; $best = $p; }
            }
            if ($best === null) continue;              // group is entirely Special (or previewing) -> keep canonical
            foreach ($printings as $p) $map[$p] = $best;
        }
        $maps[$mode] = $map;
    }
    $map = $maps[$mode];
    if (isset($map[$cardID])) return $map[$cardID];

    // Deck zones hold MIXED identities: decks saved before the 2026-08-04 SET_NNN re-key still store
    // FFG UUIDs, and the render seam passes the stored CardID straight in. Normalise and retry, or a
    // legacy deck silently shows the old printing while a modern one shows the new — the same deck,
    // two answers. (Export escapes this only because it normalises before calling us.)
    //
    // Deliberately narrow: we return a substitution ONLY when the normalised id genuinely maps to a
    // DIFFERENT printing. An id with no reprint mapping passes through untouched, so legacy cards keep
    // their stored UUID form and nothing changes for the ~2300 cards this feature is not about.
    if (function_exists('SWUNormalizeDictionaryKey')) {
        $norm = SWUNormalizeDictionaryKey($cardID);
        if ($norm !== $cardID && isset($map[$norm]) && $map[$norm] !== $norm) return $map[$norm];
    }
    return $cardID;
}
