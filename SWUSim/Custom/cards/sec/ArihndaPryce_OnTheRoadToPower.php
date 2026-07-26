<?php
// SEC_136
// Cost 4 - Arihnda Pryce - On the Road to Power - [Aggression,Villainy] - Power 4 - HP 4
// Text: When Defeated: You may defeat another friendly unit. If you do, deal 4 damage to each enemy base.

// SEC_136 Arihnda Pryce — When Defeated: you may defeat another friendly unit; if you do, deal 4 to
// each enemy base.
$whenDefeatedAbilities["SEC_136:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self, 0);
    $friendly = [];
    foreach (SWUAllUnits('my') as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $selfUID) $friendly[] = $mz;
    }
    if (empty($friendly)) return;
    SWUQueueMayChooseTarget(intval($player), $friendly, "Defeat_another_friendly_unit?", "Choose_a_friendly_unit", "SEC_136#0");
};

$customDQHandlers["SEC_136#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    SWUDefeatUnit(intval($player), $lastDecision);
    SWUDealDamageToBase(4, OtherPlayer(intval($player)));
};
