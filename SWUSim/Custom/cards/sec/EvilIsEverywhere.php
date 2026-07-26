<?php
// SEC_247
// Evil is Everywhere
// Text: Defeat a unit with cost equal to or less than the number of Villainy aspect icons among friendly units.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_247:0"] = function($player, $mzID = '') {
// Evil is Everywhere — "Defeat a unit with cost <= the number of Villainy aspect
                          // icons among friendly units."
            global $playerID; $playerID = intval($player);
            $vill = 0;
            foreach (GetUnitsInPlay(intval($player)) as $u) {
                if (!empty($u->removed)) continue;
                foreach (SWUCardAspectIcons($u->CardID ?? '') as $ic) if ($ic === 'Villainy') $vill++;
            }
            $targets = [];
            foreach (array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter),    ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            ) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && intval(CardCost($o->CardID ?? '')) <= $vill) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Defeat_a_unit_(cost<={$vill})", "DEFEAT_UNIT");
            return;
};
