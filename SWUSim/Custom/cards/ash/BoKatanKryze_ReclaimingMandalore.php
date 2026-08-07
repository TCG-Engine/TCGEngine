<?php
// ASH_010
// Cost 10 - Bo-Katan Kryze - Reclaiming Mandalore - [Command,Heroism] - Power 4 - HP 7
// Text: Action [2 resources, Exhaust]: If you control a unit in each arena, create a Mandalorian token.
// DeployText: Other friendly Mandalorian units get +1/+0. / On Attack: If you control a unit in each arena, create a Mandalorian token.
// Epic Action: If the number of resources you control plus the number of friendly Mandalorian units is 10 or more, deploy this leader.

// ASH_010 Bo-Katan Kryze — if you control a unit in each arena, create a Mandalorian token.
$onAttackAbilities["ASH_010:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (count(ZoneSearch('myGroundArena', AnyUnitFilter)) > 0 &&
        count(ZoneSearch('mySpaceArena',  AnyUnitFilter)) > 0) {
        SWUCreateUnitToken(intval($player), 'ASH_T01');
    }
};

// ASH_010 Bo-Katan Kryze — Action [2 resources, Exhaust]: if you control a unit in each arena, create a
// Mandalorian token (ASH_T01).
$leaderAbilities["ASH_010"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUPayInlineAbilityCost($player, 2)) { SWUAfterAction($player); return; }
    if (count(ZoneSearch('myGroundArena', AnyUnitFilter)) > 0 && count(ZoneSearch('mySpaceArena', AnyUnitFilter)) > 0) {
        SWUCreateUnitToken($player, 'ASH_T01');
    }
    SWUAfterAction($player);
};
