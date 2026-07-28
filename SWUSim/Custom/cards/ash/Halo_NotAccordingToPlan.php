<?php
// ASH_223
// Cost 5 - Halo - Not According to Plan - [Cunning] - Power 4 - HP 4
// Text: Support (When you play this unit, you may attack with another unit. It gains this unit's other abilities for this attack.) / When Attack Ends: If the defending unit was defeated, give a Shield token to this unit.

// ASH_223 Halo — When Attack Ends: if the defending unit was defeated, give a Shield token to this unit.
$onAttackEndAbilities["ASH_223:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (GetSWUVar('SWU_LAST_DEFENDER_DEFEATED', '') !== '1') return;
    $o = GetZoneObject($mzID);
    if (SWUObjGone($o)) return;
    DoGiveShieldToken(intval($player), $mzID);
};
