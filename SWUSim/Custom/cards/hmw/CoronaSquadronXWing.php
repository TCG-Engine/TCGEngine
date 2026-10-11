<?php
// HMW_209 Corona Squadron X-Wing — Unit (Space) 2/2, cost 2, [Cunning][Heroism], New Republic/Vehicle/Fighter.
// "On Attack: You may ready a resource."
// A resource you control (SHD_199 Coruscant Dissident's shape). Resources are offered, not auto-picked;
// with none exhausted there is nothing to gain, so nothing is offered.

$onAttackAbilities["HMW_209:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (ZoneSearch('myResources') as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval($o->Status ?? 1) === 0) $targets[] = $mz;
    }
    SWUQueueMayChooseTarget(intval($player), $targets, "Ready_a_resource?", "Choose_a_resource_to_ready", "READY_RESOURCE");
};
