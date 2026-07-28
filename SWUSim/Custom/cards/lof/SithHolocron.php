<?php
// LOF_138
// Cost 1 - Sith Holocron - [Aggression,Villainy] - Upgrade Power 1 - Upgrade HP 1
// Text: Attach to a Force unit. / Attached unit gains: "On Attack: You may deal 2 damage to a friendly unit. If you do, this unit gets +2/+0 for this attack."

// LOF_138 Sith Holocron — attached gains "On Attack: may deal 2 to a friendly unit. If you do, this unit
// gets +2/+0 for this attack." (Generic OnAttackFromUpgrade seam; closure gets the HOST mzID.)
$onAttackAbilities["LOF_138:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self);
    $friendly = [];
    foreach (SWUAllUnits('my') as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $friendly[] = $mz;
    }
    if (empty($friendly)) return;
    SWUQueueMayChooseTarget(intval($player), $friendly, "Deal_2_to_a_friendly_unit_(then_+2/+0)?", "Choose_a_friendly_unit", "LOF_138#0|{$selfUID}");
};

$customDQHandlers["LOF_138#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    SWUDealDamageToUnit($lastDecision, 2, intval($player));
    $hostMz = SWUFindMzByUID(intval($parts[0] ?? 0)); // the attacking host gets +2/+0 for this attack
    if ($hostMz !== null) SWUApplyPhaseBuff($hostMz, 2, 0, 'LOF_138');
};
