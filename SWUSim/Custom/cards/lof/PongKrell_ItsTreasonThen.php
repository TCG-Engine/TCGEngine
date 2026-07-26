<?php
// LOF_038
// Cost 7 - Pong Krell - It's Treason, Then - [Vigilance,Villainy] - Power 2 - HP 9
// Text: Grit / When this unit completes an attack (and survives): You may defeat a unit with less remaining HP than this unit's power.

// LOF_038 Pong Krell — Grit + "completes an attack (and survives): may defeat a unit with less remaining
// HP than this unit's power." (LOF_044 "can't attack" + LOF_049 "while defending +2/+0" are wired in
// CombatLogic; LOF_047's OnDefense "give an Experience token" is wired below.)
$onAttackEndAbilities["LOF_038:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    if (SWUObjGone($self)) return;
    $pow = intval(ObjectCurrentPower($self));
    $targets = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0) < $pow) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Defeat_a_unit_with_less_HP_than_this_unit's_power?", "Choose_a_unit", "DEFEAT_UNIT");
};
