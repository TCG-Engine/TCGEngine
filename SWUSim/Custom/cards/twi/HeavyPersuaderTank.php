<?php
// TWI_167
// Cost 7 - Heavy Persuader Tank - [Aggression] - Power 6 - HP 5
// Text: Exploit 2 (While playing this card, defeat up to 2 units you control. This card costs 2 resources less for each unit defeated this way.) / When Played: You may deal 2 damage to a ground unit.

// TWI_167 Heavy Persuader Tank — "Exploit 2. When Played: You may deal 2 damage to a ground unit."
$whenPlayedAbilities["TWI_167:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = array_merge(
        ZoneSearch('myGroundArena', ['Unit', 'Token Unit', 'Leader Unit']),
        ZoneSearch('theirGroundArena', ['Unit', 'Token Unit', 'Leader Unit'])
    );
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Deal_2_damage_to_a_ground_unit?", "Choose_a_ground_unit", "DEAL_UNIT_DAMAGE|2");
};
