<?php
// ASH_033
// Cost 7 - Grand Admiral Thrawn - Orchestrating His Return - [Command,Aggression,Villainy] - Power 5 - HP 7
// Text: Support (When you play this unit, you may attack with another unit. It gains this unit's other abilities for this attack.) / When Attack Ends: If the defending unit was defeated, ready this unit.

// ASH_033 Grand Admiral Thrawn — When Attack Ends: if the defending unit was defeated, ready this unit.
$onAttackEndAbilities["ASH_033:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (GetSWUVar('SWU_LAST_DEFENDER_DEFEATED', '') !== '1') return;
    $o = GetZoneObject($mzID);
    if (SWUObjGone($o)) return;
    OnReadyCard(intval($player), $mzID);
};
