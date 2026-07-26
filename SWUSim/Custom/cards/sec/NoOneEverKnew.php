<?php
// SEC_196
// No One Ever Knew
// Text: For each friendly Official unit, exhaust an enemy unit.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_196:0"] = function($player, $mzID = '') {
// No One Ever Knew — For each friendly Official unit, exhaust an enemy unit.
            global $playerID; $playerID = intval($player);
            $n = 0;
            foreach (GetUnitsInPlay(intval($player)) as $u) {
                if (empty($u->removed) && HasTrait($u->CardID ?? '', 'Official')) $n++;
            }
            for ($i = 0; $i < $n; $i++) {
                $enemies = array_values(array_merge(ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)));
                if (empty($enemies)) break;
                SWUQueueChooseTarget(intval($player), $enemies, "Exhaust_an_enemy_unit", "EXHAUST_UNIT");
            }
            return;
};
