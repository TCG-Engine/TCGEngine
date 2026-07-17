<?php

include_once __DIR__ . '/../../AppCore/SWU/Formats.php'; // SWUGetFormat

// Pure: how many leaders a format allows (1 for every format except Twin Suns). Extracted from
// ValidateLeaderAddition so the capacity rule is testable without a live gamestate.
function SWUDeckMaxLeaders($formatId) {
    $fmt = SWUGetFormat($formatId);
    return $fmt['leaderCount'] ?? 1;
}

function ValidateMainDeckAddition($cardID) {
    $deck = &GetMainDeck(1);
    $numCard = 0;
    $cardMax = 3;
    if($cardID == "2177194044") {
        $cardMax = 15;
    }
    foreach($deck as $card) {
        if($card->CardID == $cardID && !$card->Removed()) {
            $numCard++;
        }
    }
    $sideboard = &GetSideboard(1);
    foreach($sideboard as $card) {
        if($card->CardID == $cardID && !$card->Removed()) {
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
