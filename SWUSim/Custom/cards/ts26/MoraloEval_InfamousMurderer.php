<?php
// TS26_73
// Cost 3 - Moralo Eval - Infamous Murderer - [Cunning,Villainy] - Power 3 - HP 2
// Text: Shielded (When you play this unit, give a Shield token to him.) / When your base is dealt combat damage: You may deal 1 damage to a unit.

// TS26_73 Moralo Eval — the base owner (Moralo's controller) may deal 1 damage to a unit after their
// base takes combat damage. Runs under the base owner's frame (queued as a CUSTOM from SWUDealDamageToBase).
$customDQHandlers["TS26_73#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $tg = SWUAllUnits();
    if (empty($tg)) return;
    SWUQueueMayChooseTarget(intval($player), $tg, "Deal_1_damage_to_a_unit?", "Choose_a_unit", "DEAL_UNIT_DAMAGE|1");
    // leave $playerID = $player so the queued MZMAYCHOOSE validates the base owner's mzIDs
};
