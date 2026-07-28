<?php
// SOR_043
// Superlaser Blast
// Text: Vigilance,Villainy

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_043:0"] = function($player, $mzID = '') {
// Superlaser Blast — "Defeat all units." Snapshot every unit's UID across all
                          // four arenas (incl. deployed leaders + tokens), then defeat by UID so the
                          // index shift from each defeat can't stale the others (simultaneous mass-defeat
                          // through the SWUDefeatUnit collector, which fires each WhenDefeated).
            global $playerID;
            $playerID = intval($player);
            $uids = [];
            foreach (["myGroundArena", "mySpaceArena", "theirGroundArena", "theirSpaceArena"] as $zone) {
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
