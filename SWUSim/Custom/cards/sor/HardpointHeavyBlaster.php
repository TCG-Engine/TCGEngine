<?php
// SOR_121
// Cost 2 - Hardpoint Heavy Blaster - [Command] - Upgrade Power 2 - Upgrade HP 2
// Text: Attach to a VEHICLE unit. / Attached unit gains: "On Attack: If this unit isn't attacking a base, you may deal 2 damage to a unit in the defender's arena."

// SOR_121 Hardpoint Heavy Blaster (upgrade) — granted On Attack: if not attacking a base,
// you may deal 2 to a unit in the defender's arena. $mzID = host unit; defender via SWU var.
$onAttackAbilities["SOR_121:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $defenderMz = GetSWUVar('SWU_CURRENT_DEFENDER');
    if ($defenderMz === '' || $defenderMz === '-') return;
    if (strpos($defenderMz, 'Arena') === false) return;   // attacking a base → no effect
    $arena = (strpos($defenderMz, 'Ground') !== false) ? 'GroundArena' : 'SpaceArena';
    $targets = array_merge(
        ZoneSearch("my{$arena}",    AnyUnitFilter),
        ZoneSearch("their{$arena}", AnyUnitFilter)
    );
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Deal_2_to_a_unit_in_the_defenders_arena?", "Deal_2_to_a_unit_in_the_arena", "DEAL_UNIT_DAMAGE|2");
};
