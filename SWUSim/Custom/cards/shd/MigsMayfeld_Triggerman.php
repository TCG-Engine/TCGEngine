<?php
// SHD_163
// Cost 2 - Migs Mayfeld - Triggerman - [Aggression] - Power 2 - HP 2
// Text: When a player discards a card from their hand: You may deal 2 damage to a unit or base. Use this ability only once each round.

// ─── SHD_163 Migs Mayfeld (reactive: any hand-discard → may deal 2 to a unit/base, once/round) ───
$customDQHandlers["SHD_163#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    if (strpos($lastDecision, 'Base') !== false) {
        $bp = (strpos($lastDecision, 'myBase') !== false) ? intval($player) : OtherPlayer(intval($player));
        SWUDealDamageToBase(2, $bp);
    } else {
        $o = GetZoneObject($lastDecision);
        if (SWUObjGone($o)) return;
        SWUDealDamageToUnit($lastDecision, 2, intval($player));
    }
    AddGlobalEffects(intval($player), 'SWU_SHD163_USED');   // once each round — consumed on actual use
};
