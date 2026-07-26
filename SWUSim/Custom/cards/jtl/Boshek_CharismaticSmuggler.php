<?php
// JTL_215
// Cost 3 - BoShek - Charismatic Smuggler - [Cunning] - Power 3 - HP 4 - Upgrade Power 1 - Upgrade HP 2
// Text: / Piloting [2 resources Cunning] (You may play this as an upgrade on a friendly Vehicle without a Pilot.) / When played as an upgrade: Discard 2 cards from your deck. Return each of those cards with an odd cost to your hand.

// JTL_215 BoShek (pilot) — When played as an upgrade: Discard 2 cards from your deck. Return each of
// those cards with an odd cost to your hand. (Odd-cost milled cards route straight to hand.)
$whenPlayedAsUpgradeAbilities["JTL_215:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $deck = &GetDeck(intval($player));
    for ($i = 0; $i < 2; $i++) {
        $idx = _SWUTopDeckFrontIdx(intval($player));
        if ($idx === -1) break;
        $cid = $deck[$idx]->CardID;
        $deck[$idx]->removed = true;
        if ((intval(CardCost($cid)) % 2) === 1) AddHand(intval($player), CardID: $cid);   // odd → hand
        else SWUAddToDiscard(intval($player), $cid, 'DECK');                                // even → discard
    }
    DecisionQueueController::CleanupRemovedCards();
};
