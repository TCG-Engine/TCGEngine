<?php
// ASH_168
// Cost 2 - Migs Mayfeld - How About a Toast? - [Aggression] - Power 2 - HP 3
// Text: Support / On Attack: Deal 1 damage to the defending unit. If this unit is upgraded, deal 2 damage to the defending unit instead.

// ASH_168 Migs Mayfeld — On Attack: deal 1 damage to the defending unit; if this unit is upgraded,
// deal 2 instead. Fires for ASH_168's own attacks AND (via the SUPPORT_GRANT graft) for a unit it
// supports — in which case $mzID is the supported attacker, so "this unit" resolves to that attacker.
$onAttackAbilities["ASH_168:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $def = GetSWUVar('SWU_CURRENT_DEFENDER', '');
    if ($def === '' || $def === '-' || strpos($def, 'Arena') === false) return; // unit defender only (not a base)
    $defObj = GetZoneObject($def);
    if (SWUObjGone($defObj)) return;
    $self = GetZoneObject($mzID);
    $amt  = ($self !== null && _SWUIsUpgraded($self)) ? 2 : 1;
    SWUDealDamageToUnit($def, $amt, intval($player));
};
