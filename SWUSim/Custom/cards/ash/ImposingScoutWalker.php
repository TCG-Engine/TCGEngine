<?php
// ASH_176
// Cost 6 - Imposing Scout Walker - [Aggression] - Power 4 - HP 6
// Text: When Played: You may deal 3 damage to a ground unit. If it's defeated this way, give 3 Advantage tokens to this unit.

// ASH_176 Imposing Scout Walker — When Played: you may deal 3 damage to a ground unit; if it's defeated
// this way, give 3 Advantage tokens to this unit.
$whenPlayedAbilities["ASH_176:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID); $selfUid = SWUObjUID($self, 0);
    $targets = SWUAllUnits(null, GroundArena);
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Deal_3_to_a_ground_unit_(3_Advantage_to_self_if_it_dies)?", "Choose_a_ground_unit", "ASH_176#0|{$selfUid}");
};

$customDQHandlers["ASH_176#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $selfUid = intval($parts[0] ?? 0);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $tuid = intval($o->UniqueID ?? 0);
    SWUDealDamageToUnit($lastDecision, 3, intval($player));
    if (SWUFindMzByUID($tuid) === null) {   // target defeated this way
        $selfMz = SWUFindMzByUID($selfUid);
        if ($selfMz !== null) { for ($i = 0; $i < 3; $i++) DoGiveAdvantageToken(intval($player), $selfMz); }
    }
};
