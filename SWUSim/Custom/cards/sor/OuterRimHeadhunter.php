<?php
// SOR_208
// Cost 2 - Outer Rim Headhunter - [Cunning] - Power 1 - HP 3
// Text: Raid 1 (This unit gets +1/+0 while attacking.) / On Attack: If you control a leader unit, you may exhaust a non-leader unit.

// SOR_208 Outer Rim Headhunter — On Attack: If you control a leader unit, you may exhaust a
// non-leader unit. (Raid 1 is an auto keyword.)
$onAttackAbilities["SOR_208:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    if (!SWUControlsLeaderUnit(intval($player))) return;
    SWUQueueMayChooseTarget(intval($player),
        _SWUCollectUnits(-1, fn($o) => !IsLeaderUnit($o)),
        'Exhaust_a_non-leader_unit?', 'Choose_a_non-leader_unit_to_exhaust', 'EXHAUST_UNIT');
};
