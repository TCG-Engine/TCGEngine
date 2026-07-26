<?php
// TWI_149
// Cost 6 - Low Altitude Gunship - [Aggression,Heroism] - Power 6 - HP 5
// Text: Overwhelm (When attacking an enemy unit, deal excess damage to the opponent's base.) / When Played: Choose an enemy unit. Deal 1 damage to it for each friendly Republic unit.

// TWI_149 Low Altitude Gunship — "When Played: Choose an enemy unit. Deal 1 damage to it for each
// friendly Republic unit." (Overwhelm is a keyword; amount computed at resolution, includes this unit.)
$whenPlayedAbilities["TWI_149:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = SWUAllUnits('their');
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Deal_1_per_friendly_Republic_unit_to_an_enemy_unit", "TWI_149#0");
};

$customDQHandlers["TWI_149#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $count = 0;
    foreach (["myGroundArena", "mySpaceArena"] as $z) {
        foreach (ZoneSearch($z, ['Unit', 'Token Unit', 'Leader Unit']) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && HasTrait($o->CardID ?? '', 'Republic')) $count++;
        }
    }
    if ($count > 0) SWUDealDamageToUnit($lastDecision, $count, intval($player));
};
