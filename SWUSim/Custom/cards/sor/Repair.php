<?php
// SOR_074
// Repair
// Text: Heal 3 damage from a unit or base.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_074:0"] = function($player, $mzID = '') {
// Repair — "Heal 3 damage from a unit or base." Bases ARE valid
            // MZCHOOSE targets via myBase-0 / theirBase-0 (GetZone recognizes those zones).
            $targets = array_merge(
                SWUAllUnits(),   // "A UNIT or base" is unqualified -> every unit on the table
                SWUAllBaseMzIDs(intval($player), 'any')
            );
            // USER RULING 2026-08-13: prompt ONLY when something (either side) has damage. The pool is
            // NEVER filtered to damaged targets — with any damage present, undamaged targets stay
            // pickable so a player can deliberately "soft pass" by healing 0 from their own unit/base.
            // With zero damage anywhere the prompt is pointless and is skipped entirely.
            $anyDamage = false;
            foreach ($targets as $tmz) {
                $o = GetZoneObject($tmz);
                if ($o !== null && empty($o->removed) && intval($o->Damage ?? 0) > 0) { $anyDamage = true; break; }
            }
            if (!$anyDamage) return;
            DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $targets), 1, "Heal_3_from_a_unit_or_base");
            DecisionQueueController::AddDecision($player, "CUSTOM", "HEAL_TARGET|3", 1);
            return;
};
