<?php
// SEC_256
// Cost 3 - Moral Authority - [Heroism] - Upgrade Power 2 - Upgrade HP 0
// Text: Attach to a friendly <uq> (unique) unit. / When Played: Attached unit captures an enemy non-leader unit with less remaining HP than it.

// SEC_256 Moral Authority (Upgrade, attach to a friendly unique unit) — When Played: attached unit
// captures an enemy non-leader unit with less remaining HP than it. (whenPlayed on an upgrade → $mzID = host.)
$whenPlayedAbilities["SEC_256:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if (SWUObjGone($host)) return;
    $hostUID  = intval($host->UniqueID ?? 0);
    $hostRem  = intval(ObjectCurrentHP($host)) - intval($host->Damage ?? 0);
    $targets = [];
    foreach (array_merge(ZoneSearch("theirGroundArena", NonLeaderUnitFilter), ZoneSearch("theirSpaceArena", NonLeaderUnitFilter)) as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if ((intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0)) < $hostRem) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Capture_an_enemy_unit_with_less_remaining_HP", "SEC_253#0|{$hostUID}");
};
