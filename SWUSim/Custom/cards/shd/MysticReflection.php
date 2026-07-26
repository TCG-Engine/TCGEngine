<?php
// SHD_051
// Mystic Reflection
// Text: Give an enemy unit -2/-0 for this phase. If you control a Force unit, give the enemy unit -2/-2 for this phase instead.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_051:0"] = function($player, $mzID = '') {
// Mystic Reflection — "Give an enemy unit -2/-0 for this phase. If you control a
                          // Force unit, give the enemy unit -2/-2 for this phase instead."
            global $playerID; $playerID = intval($player);
            $enemies = [];
            foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
                foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed)) $enemies[] = $mz;
                }
            }
            if (empty($enemies)) return;
            $hasForce = false;
            foreach (GetUnitsInPlay(intval($player)) as $u) {
                if (empty($u->removed) && HasTrait($u->CardID ?? '', 'Force')) { $hasForce = true; break; }
            }
            $hp = $hasForce ? 2 : 0;
            SWUQueueChooseTarget(intval($player), $enemies, "Give_an_enemy_unit_-2/-{$hp}_this_phase", "APPLY_PHASE_DEBUFF|2|{$hp}|SHD_051");
            return;
};
