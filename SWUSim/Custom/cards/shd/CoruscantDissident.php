<?php
// SHD_199
// Cost 3 - Coruscant Dissident - [Cunning,Heroism] - Power 3 - HP 4
// Text: On Attack: You may ready a resource.

// ─── SHD_199 Coruscant Dissident ──────────────────────────────────────────────
// On Attack: You may ready a resource.
$onAttackAbilities["SHD_199:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (ZoneSearch('myResources') as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval($o->Status ?? 1) === 0) $targets[] = $mz;
    }
    SWUQueueMayChooseTarget(intval($player), $targets, "Ready_a_resource?", "Choose_a_resource", "READY_RESOURCE");
};
