<?php
// JTL_215
// Cost 3 - BoShek - Charismatic Smuggler - [Cunning] - Power 3 - HP 4 - Upgrade Power 1 - Upgrade HP 2
// Text: / Piloting [2 resources Cunning] (You may play this as an upgrade on a friendly Vehicle without a Pilot.) / When played as an upgrade: Discard 2 cards from your deck. Return each of those cards with an odd cost to your hand.

// JTL_215 BoShek (pilot) — When played as an upgrade: Discard 2 cards from your deck. Return each of
// those cards with an odd cost to your hand. BOTH cards are DISCARDED first (the text says "discard…
// return" — when-discarded triggers and discard stamps fire for the odd card too), then each odd-cost
// entry returns from the discard to hand. Routing odd cards straight to hand skipped the discard
// event entirely (the alternate-storage-path family).
$whenPlayedAsUpgradeAbilities["JTL_215:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $deck = &GetDeck(intval($player));
    for ($i = 0; $i < 2; $i++) {
        $idx = _SWUTopDeckFrontIdx(intval($player));
        if ($idx === -1) break;
        $cid = $deck[$idx]->CardID;
        $deck[$idx]->removed = true;
        $entry = SWUAddToDiscard(intval($player), $cid, 'DECK');
        if ((intval(CardCost($cid)) % 2) === 1) {                 // odd → return from discard to hand
            if ($entry !== null) $entry->removed = true;
            AddHand(intval($player), CardID: $cid);
        }
    }
    DecisionQueueController::CleanupRemovedCards();
};
