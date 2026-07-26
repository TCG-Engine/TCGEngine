<?php
// SEC_139
// Cost 5 - Miraj Scintel - The Weak Deserve to Kneel - [Aggression,Villainy] - Power 3 - HP 7
// Text: While a friendly unit is attacking a damaged unit, the attacker gains Overwhelm. / When Played: You may deal 3 damage to an undamaged unit.

// SEC_139 Miraj Scintel — (Overwhelm passive in CombatLogic) + When Played: may deal 3 to an undamaged unit.
$whenPlayedAbilities["SEC_139:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval($o->Damage ?? 0) === 0) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Deal_3_to_an_undamaged_unit?", "Choose_an_undamaged_unit", "DEAL_UNIT_DAMAGE|3");
};
