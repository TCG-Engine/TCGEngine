<?php
// LAW_169
// Payroll Heist
// Text: For this phase, each friendly unit gains: "On Attack: Create a Credit token."

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LAW_169:0"] = function($player, $mzID = '') {
// Payroll Heist — "For this phase, each friendly unit gains: On Attack: Create
                          // a Credit token." Tag each friendly unit in play now with the LAW_169 marker
                          // (read at attack time in ExecuteSWUAttack). Units entering later aren't marked.
            global $playerID; $playerID = intval($player);
            foreach (array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter)) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed)) AddTurnEffect($mz, 'LAW_169');
            }
            return;
};
