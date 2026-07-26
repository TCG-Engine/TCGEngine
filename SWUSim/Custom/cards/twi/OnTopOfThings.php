<?php
// TWI_219
// Cost 2 - On Top of Things - [Cunning] - Upgrade Power 2 - Upgrade HP 0
// Text: When Played: Attached unit can't be attacked this phase (unless it has Sentinel).

// TWI_219 On Top of Things — "When Played: Attached unit can't be attacked this phase (unless it has
// Sentinel)." (Upgrade +2/+0; $mzID = the host.)
$whenPlayedAbilities["TWI_219:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if (SWUObjGone($host)) return;
    if (HasKeyword_Sentinel($host)) return; // "unless it has Sentinel" — a Sentinel unit stays attackable
    AddTurnEffect($mzID, 'CANT_BE_ATTACKED'); // phase-duration; read in SWUGetValidAttackTargets
};
