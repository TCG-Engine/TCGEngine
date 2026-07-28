<?php
// SEC_162
// Cost 2 - Crosshair - Filled With Doubt - [Aggression] - Power 2 - HP 3
// Text: On Attack: You may deal 1 damage to another friendly unit. If you do, deal 2 damage to the defending player's base.

// SEC_162 Crosshair — On Attack: you may deal 1 to another friendly unit; if you do, deal 2 to the
// defending player's base.
$onAttackAbilities["SEC_162:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self, 0);
    $friendly = [];
    foreach (SWUAllUnits('my') as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $selfUID) $friendly[] = $mz;
    }
    if (empty($friendly)) return;
    SWUQueueMayChooseTarget(intval($player), $friendly, "Deal_1_to_a_friendly_unit_to_deal_2_to_the_base?", "Choose_another_friendly_unit", "SEC_162#0");
};

$customDQHandlers["SEC_162#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    SWUDealDamageToUnit($lastDecision, 1, intval($player));
    SWUDealDamageToBase(2, OtherPlayer(intval($player)));
};
