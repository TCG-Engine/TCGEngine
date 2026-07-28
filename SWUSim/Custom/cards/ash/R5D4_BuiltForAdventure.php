<?php
// ASH_156
// Cost 3 - R5-D4 - Built for Adventure - [Aggression,Heroism] - Power 3 - HP 4
// Text: Support (When you play this unit, you may attack with another unit. It gains this unit's other abilities for this attack.) / On Attack: Defeat all upgrades on the defending unit.

// ASH_156 R5-D4 — On Attack: defeat all upgrades on the defending unit.
$onAttackAbilities["ASH_156:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $def = GetSWUVar('SWU_CURRENT_DEFENDER', '');
    if ($def === '' || $def === '-' || strpos($def, 'Arena') === false) return; // unit defender only
    $defObj = GetZoneObject($def);
    if (SWUObjGone($defObj)) return;
    _SWUDefeatAllUpgradesOn($def);
};
