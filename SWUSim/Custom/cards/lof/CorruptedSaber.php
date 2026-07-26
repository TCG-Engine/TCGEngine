<?php
// LOF_187
// Cost 2 - Corrupted Saber - [Cunning,Villainy] - Upgrade Power 2 - Upgrade HP 1
// Text: Attach to a non-Vehicle unit. / If attached unit is a Force unit, it gains: "On Attack: The defender gets -2/-0 for this attack."

// LOF_187 Corrupted Saber — if attached unit is a Force unit, gains "On Attack: the defender gets -2/-0
// for this attack." (SWU_DEF_DEBUFF_N marker on the attacker, consumed in combat.)
$onAttackAbilities["LOF_187:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if ($host === null || !TraitContains($host, 'Force')) return;
    AddTurnEffect($mzID, 'SWU_DEF_DEBUFF_2');
};
