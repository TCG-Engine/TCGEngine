<?php

include_once __DIR__ . '/../../AppCore/SWU/Formats.php'; // SWUGetFormat
include_once __DIR__ . '/../../AppCore/SWU/Overrides.php'; // CardIDOverride — reprint → earliest printing

// Pure: how many leaders a format allows (1 for every format except Twin Suns). Extracted from
// ValidateLeaderAddition so the capacity rule is testable without a live gamestate.
function SWUDeckMaxLeaders($formatId) {
    $fmt = SWUGetFormat($formatId);
    return $fmt['leaderCount'] ?? 1;
}

// Pure: how many copies of $cardID the format allows — its maxCopies, overridden by any
// copyException (e.g. Swarming Vulture Droid's "a deck can have up to 15 copies of this card").
// Same rule SWUCheckFormat applies at save time (AppCore/SWU/DeckValidation.php), keyed the same
// way: by CANONICAL printing, so every reprint shares one limit.
//
// This used to be a hardcoded `3` plus `if($cardID == "2177194044") $cardMax = 15;` — the Vulture
// Droid's pre-migration FFG UUID. The 2026-08-06 SET_NNN migration re-keyed every card ("JTL_256"),
// so that comparison could never be true again and the builder capped the card at 3 while the
// validator happily accepted 15. Deriving the limit from the format config means it cannot drift
// again: a new copy-exception or a highlander format is picked up here for free.
function SWUDeckMaxCopies($cardID, $formatId) {
    $fmt = SWUGetFormat($formatId);
    if ($fmt === null) return 3;   // unknown format: the standard limit
    return $fmt['copyExceptions'][SWUDeckCanonicalCardID($cardID)] ?? $fmt['maxCopies'];
}

// The identity two copies are "the same card" under: the earliest printing (CR 8.36), expressed as
// a SET_NNN id. Deck files written before the 2026-08-06 migration still hold FFG UUIDs — and a
// single deck can hold both, since cards added after the migration are stored as SET_NNN — so the
// stored id must be normalized before the reprint map can canonicalize it. Compare raw ids and the
// same card counts twice under two spellings, which lets a 4th copy past the gate.
function SWUDeckCanonicalCardID($cardID) {
    $id = function_exists('SWUNormalizeDictionaryKey') ? SWUNormalizeDictionaryKey($cardID) : $cardID;
    return CardIDOverride($id);
}

function ValidateMainDeckAddition($cardID) {
    global $gameName;
    $format = LoadAssetData(1, $gameName)['format'] ?? 'premier';
    $cardMax = SWUDeckMaxCopies($cardID, $format);

    // Count by canonical printing across BOTH zones: the copy limit spans the sideboard (cards swap
    // 1-for-1 between games) and reprints share a limit — matching SWUCheckFormat exactly, so the
    // builder never accepts a deck the save-time validator will reject.
    $canonical = SWUDeckCanonicalCardID($cardID);
    $numCard = 0;
    $deck = &GetMainDeck(1);
    foreach($deck as $card) {
        if(SWUDeckCanonicalCardID($card->CardID) == $canonical && !$card->Removed()) {
            $numCard++;
        }
    }
    $sideboard = &GetSideboard(1);
    foreach($sideboard as $card) {
        if(SWUDeckCanonicalCardID($card->CardID) == $canonical && !$card->Removed()) {
            $numCard++;
        }
    }
    return $numCard < $cardMax;
}

// Leader selection from the browse panes (Leader1 / Leader2 / Leaders → Click: Add(myLeader)).
// Rather than the old "reject when the single slot is full" gate — which left users with no way to
// change a leader once one was set except by clicking it off the identity banner (now a cosmetic,
// non-interactive readout) — this REPLACES the leader occupying the clicked pane's slot:
//
//   * Leader2 pane  -> slot 2 (Twin Suns only)
//   * Leader1 / Leaders pane (or any other source) -> slot 1
//
// The change is applied here in-place (rebuild the leader zone in slot order, update the
// keyIndicator thumbnails) and the function returns FALSE so the generated AddLeader() does NOT
// also append the card — we've already placed it exactly where it belongs. The array order is kept
// equal to slot order so a later swap targets the right leader. Import (CreateDeck/RefreshImport)
// never reaches this function — it array_pushes leaders directly — so $gEngineActionSourceMZID is
// always a real pane mzid ("myLeader1-3" / "myLeader2-3" / "myLeaders-3") here; EngineActionRunner
// records it per action.
function ValidateLeaderAddition($cardID) {
    global $gameName;
    $format = LoadAssetData(1, $gameName)['format'] ?? 'premier';
    $maxLeaders = SWUDeckMaxLeaders($format);

    $sourceZone = explode('-', $GLOBALS['gEngineActionSourceMZID'] ?? '')[0];
    $targetSlot = ($maxLeaders >= 2 && $sourceZone === 'myLeader2') ? 2 : 1;
    $idx = $targetSlot - 1;

    // Current leaders in slot order (this function keeps array order == slot order).
    $zone = &GetLeader(1);
    $slots = [];
    foreach ($zone as $l) { if (!$l->Removed()) $slots[] = $l->CardID; }
    $slots = array_slice($slots, 0, $maxLeaders); // self-heal any stale over-capacity

    // Don't allow the same leader in both slots (Twin Suns).
    foreach ($slots as $i => $cid) {
        if ($i !== $idx && $cid === $cardID) return false;
    }

    // Place the new leader into its slot: replace what's there, or fill the next empty slot.
    if ($idx < count($slots)) {
        $slots[$idx] = $cardID;
    } else if (count($slots) < $maxLeaders) {
        $slots[] = $cardID;
    } else {
        return false; // no capacity (shouldn't happen given the slot logic above)
    }

    // Rebuild the leader zone in slot order (hard clear — MZClearZone would only mark Removed and
    // leave the array growing with dead entries across repeated swaps).
    array_splice($zone, 0, count($zone));
    foreach ($slots as $i => $cid) {
        $obj = new Leader($cid, 'Leader', 1);
        $obj->mzIndex = $i;
        array_push($zone, $obj);
    }

    // Deck-list / identity-banner thumbnails: keyIndicator1 = slot 1, keyIndicator3 = slot 2.
    SetAssetKeyIdentifier(1, $gameName, 1, $slots[0] ?? null);
    SetAssetKeyIdentifier(1, $gameName, 3, $slots[1] ?? null);

    return false; // change already applied in-place; suppress AddLeader()'s own append.
}

function ValidateBaseAddition($cardID) {
    global $gameName;
    SetAssetKeyIdentifier(1, $gameName, 2, $cardID);
    return true;
}

?>
