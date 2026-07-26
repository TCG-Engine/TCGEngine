<?php
// SOR_218
// Asteroid Sanctuary
// Text: Exhaust an enemy unit.  Give a Shield token to a friendly unit that costs 3 or less.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_218:0"] = function($player, $mzID = '') {
// Asteroid Sanctuary — "Exhaust an enemy unit. Give a Shield to a friendly unit that costs 3 or less."
            global $playerID;
            $playerID = intval($player);
            $enemies = array_merge(
                ZoneSearch('theirGroundArena', AnyUnitFilter),
                ZoneSearch('theirSpaceArena',  AnyUnitFilter)
            );
            SWUQueueChooseTarget(intval($player), $enemies, 'Exhaust_an_enemy_unit', 'EXHAUST_UNIT');
            $friendlies = [];
            foreach (array_merge(
                ZoneSearch('myGroundArena', AnyUnitFilter),
                ZoneSearch('mySpaceArena',  AnyUnitFilter)
            ) as $mz) {
                $o = GetZoneObject($mz);
                if (SWUObjGone($o)) continue;
                if (intval(CardCost($o->CardID) ?? 99) <= 3) $friendlies[] = $mz;
            }
            SWUQueueChooseTarget(intval($player), $friendlies, 'Give_a_Shield_to_a_friendly_unit_(cost_3_or_less)', 'GIVE_SHIELD');
            return;
};
