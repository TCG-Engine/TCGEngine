<?php
// TWI_215
// Cost 5 - Geonosis Patrol Fighter - [Cunning] - Power 3 - HP 2
// Text: Exploit 2 (While playing this card, defeat up to 2 units you control. This card costs 2 resources less for each unit defeated this way.) / When Played: You may return a non-leader unit that costs 3 or less to its owner's hand.

// TWI_215 Geonosis Patrol Fighter — "Exploit 2. When Played: You may return a non-leader unit that
// costs 3 or less to its owner's hand."
$whenPlayedAbilities["TWI_215:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, NonLeaderUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval(CardCost($o->CardID)) <= 3) $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Return_a_non-leader_unit_costing_3_or_less_to_hand?", "Choose_a_unit_to_return", "BOUNCE_UNIT");
};
