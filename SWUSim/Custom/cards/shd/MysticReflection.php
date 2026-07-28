<?php
// SHD_051
// Mystic Reflection
// Text: Give an enemy unit -2/-0 for this phase. If you control a Force unit, give the enemy unit -2/-2 for this phase instead.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_051:0"] = function($player, $mzID = '') {
// Mystic Reflection — "Give an enemy unit -2/-0 for this phase. If you control a
                          // Force unit, give the enemy unit -2/-2 for this phase instead."
            global $playerID; $playerID = intval($player);
            $hasForce = false;
            foreach (GetUnitsInPlay(intval($player)) as $u) {
                if (empty($u->removed) && TraitContains($u, 'Force')) { $hasForce = true; break; }
            }
            $hp = $hasForce ? 2 : 0;
            SWUOfferUnitTarget(intval($player), $mzID, [
                'continuation' => "APPLY_PHASE_DEBUFF|2|{$hp}|SHD_051",
                'side'         => 'their',
                'prompt'       => "Give_an_enemy_unit_-2/-{$hp}_this_phase",
            ]);
            return;
};
