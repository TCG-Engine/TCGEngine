<?php
// SEC_078
// Hyperspace Disaster
// Text: Defeat all space units.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_078:0"] = function($player, $mzID = '') {
// Hyperspace Disaster — "Defeat all space units." (snapshot UIDs, then defeat by UID)
            global $playerID; $playerID = intval($player);
            $uids = [];
            // ⚠ UNQUALIFIED pool = the WHOLE table, so the own side is 'team*', not 'my*': `their*` is the
            // OPPONENT fan-out and excludes a teammate, so my*+their* leaves a teammate's units in NEITHER
            // list. 'team*' degrades to 'my*' outside a team game (Premier byte-identical).
            foreach (["teamSpaceArena", "theirSpaceArena"] as $zone) {
                foreach (ZoneSearch($zone, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID);
                }
            }
            // One simultaneous defeat, walked a unit at a time — so "when an enemy unit is defeated"
            // observers caught in the wipe (ASH_052 Chimaera, itself a space unit, so ALWAYS a casualty
            // here; SOR_002 Iden Versio; TS26_13 Darth Sidious) must be judged against the pre-effect
            // board. Without the window each defeat is its own single-element batch and an observer
            // removed early stops counting. See SWUSimulDefeatBegin (GameLogic.php).
            SWUSimulDefeatBegin();
            foreach ($uids as $uid) {
                $playerID = intval($player);
                $mz = SWUFindMzByUID($uid);
                if ($mz !== null) SWUDefeatUnit(intval($player), $mz);
            }
            SWUSimulDefeatEnd();
            return;
};
