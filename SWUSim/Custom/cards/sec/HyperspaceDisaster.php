<?php
// SEC_078
// Hyperspace Disaster
// Text: Defeat all space units.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_078:0"] = function($player, $mzID = '') {
// Hyperspace Disaster — "Defeat all space units." (snapshot UIDs, then defeat by UID)
            global $playerID; $playerID = intval($player);
            $uids = [];
            foreach (["mySpaceArena", "theirSpaceArena"] as $zone) {
                foreach (ZoneSearch($zone, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID);
                }
            }
            foreach ($uids as $uid) {
                $playerID = intval($player);
                $mz = SWUFindMzByUID($uid);
                if ($mz !== null) SWUDefeatUnit(intval($player), $mz);
            }
            return;
};
