<?php
// JTL_106
// Unity of Purpose
// Text: For each friendly unit with a different name, give each unit you control +1/+1 for this phase.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_106:0"] = function($player, $mzID = '') {
// Unity of Purpose — for each friendly unit with a DIFFERENT name, give each
                          // unit you control +1/+1 this phase. N = number of distinct names among your
                          // units; every friendly unit gets +N/+N.
            global $playerID;
            $playerID = intval($player);
            $myUnits = array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter));
            if (empty($myUnits)) return;
            $names = [];
            foreach ($myUnits as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed)) $names[CardTitle($o->CardID)] = true;
            }
            $n = count($names);
            if ($n <= 0) return;
            foreach ($myUnits as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed)) SWUApplyPhaseBuff($mz, $n, $n, 'JTL_106');
            }
            return;
};
