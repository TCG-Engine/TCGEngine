<?php
// JTL_080
// Nebula Ignition
// Text: Defeat each unit that isn't upgraded.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_080:0"] = function($player, $mzID = '') {
// Nebula Ignition — defeat each unit that isn't upgraded (no attached upgrades,
                          // including token upgrades). Snapshot UIDs first (mass defeat is index-unstable).
            global $playerID;
            $playerID = intval($player);
            $uids = [];
            foreach (array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            ) as $mz) {
                $o = GetZoneObject($mz);
                if (SWUObjGone($o)) continue;
                if (empty(GetUpgradesOnUnit($o))) $uids[] = intval($o->UniqueID ?? 0);
            }
            foreach ($uids as $uid) {
                $mz = SWUFindMzByUID($uid);
                if ($mz !== null && $mz !== '') SWUDefeatUnit(intval($player), $mz);
            }
            return;
};
