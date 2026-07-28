<?php
// LOF_172
// Sorcerous Blast
// Text: Use the Force (lose your Force token). If you do, deal 3 damage to a unit.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_172:0"] = function($player, $mzID = '') {
// Sorcerous Blast — "Use the Force (lose your Force token). If you do, deal 3 damage to a unit."
            // CR 37.4: a player may only Use the Force if they control their Force token. If they don't,
            // they did not Use the Force, so the "If you do" rider fails and the event fizzles.
            if (!PlayerHasTheForce(intval($player))) return;
            UseTheForce(intval($player)); // defeat the Force token
            SWUOfferUnitTarget(intval($player), $mzID, [
                'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 3,
                'prompt' => "Deal_3_damage_to_a_unit",
            ]);
            return;
};
