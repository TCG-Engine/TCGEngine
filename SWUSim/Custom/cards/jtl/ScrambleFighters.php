<?php
// JTL_092
// Scramble Fighters
// Text: Create 8 TIE Fighter tokens and ready them. They can't attack bases for this phase.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_092:0"] = function($player, $mzID = '') {
// Scramble Fighters — create 8 TIE Fighter tokens, readied; they can't attack
                          // bases for this phase (per-token CANT_ATTACK_BASES marker, expires at regroup).
            global $playerID;
            $playerID = intval($player);
            // 8 TIE Fighters (Space, 1/1), readied, CANT_ATTACK_BASES this phase; the marker rides the batch
            // funnel so any Moff-Jerjerrod-doubled TIEs also can't attack bases.
            SWUCreateUnitTokens(intval($player), 'JTL_T01', 8, true, 'CANT_ATTACK_BASES');
            return;
};
