<?php
// LAW_044
// Single Reactor Ignition
// Text: Defeat all units. For each enemy unit defeated this way, deal 1 damage to its controller's base.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LAW_044:0"] = function($player, $mzID = '') {
// Single Reactor Ignition — "Defeat all units. For each enemy unit defeated
                          // this way, deal 1 damage to its controller's base." Snapshot every unit's
                          // UID + controller, defeat by UID (index-shift safe), then count the enemy
                          // (opponent-controlled) units that actually left play and deal that much to
                          // the opponent's base. Re-checking by UID respects defeat immunity (LAW_149).
            global $playerID; $playerID = intval($player);
            $opp = OtherPlayer(intval($player));
            $enemyUids = [];   // opponent-controlled units, to check post-defeat
            $allUids   = [];
            foreach (["myGroundArena", "mySpaceArena", "theirGroundArena", "theirSpaceArena"] as $zone) {
                foreach (ZoneSearch($zone, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if (SWUObjGone($o)) continue;
                    $uid = intval($o->UniqueID);
                    $allUids[] = $uid;
                    if (intval($o->Controller ?? 0) === $opp) $enemyUids[] = $uid;
                }
            }
            foreach ($allUids as $uid) {
                $playerID = intval($player);
                $mz = SWUFindMzByUID($uid);
                if ($mz !== null) SWUDefeatUnit(intval($player), $mz);
            }
            $defeatedEnemies = 0;
            foreach ($enemyUids as $uid) {
                if (SWUFindMzByUID($uid) === null) $defeatedEnemies++;
            }
            if ($defeatedEnemies > 0) SWUDealDamageToBase($defeatedEnemies, $opp);
            return;
};
