<?php
// LAW_247
// Backed by the Hutts
// Text: Create a Credit token. You may deal damage to a unit equal to the number of friendly Credit tokens.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LAW_247:0"] = function($player, $mzID = '') {
{ // Backed by the Hutts — Create a Credit token. You may deal damage to a unit
                          // equal to the number of friendly Credit tokens. (Create FIRST, then count.)
            global $playerID; $playerID = intval($player);
            SWUCreateCreditToken(intval($player), 1);
            $n = SWUCountFriendlyCreditTokens(intval($player));
            if ($n <= 0) return;
            $targets = SWUAllUnits(); // "a unit" = any unit, both players, all arenas
            if (empty($targets)) return;
            SWUQueueMayChooseTarget(intval($player), $targets,
                "Deal_{$n}_to_a_unit?", "Deal_{$n}_damage_to_a_unit", "DEAL_UNIT_DAMAGE|{$n}");
            return;
        }

        // ── SEC Events ─────────────────────────────────────────────────────────
};
