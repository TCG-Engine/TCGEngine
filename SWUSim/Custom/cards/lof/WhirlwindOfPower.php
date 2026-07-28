<?php
// LOF_078
// Whirlwind of Power
// Text: Give a unit -2/-2 for this phase. If you control a Force unit, give it -3/-3 instead.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_078:0"] = function($player, $mzID = '') {
// Whirlwind of Power — "Give a unit -2/-2 for this phase. If you control a Force
                          // unit, give it -3/-3 instead."
            $n = PlayerHasUnitWithTraitInPlay(intval($player), 'Force', -1) ? 3 : 2;
            SWUOfferUnitTarget($player, $mzID, [
                'continuation' => "APPLY_PHASE_DEBUFF|{$n}|{$n}|LOF_078",
                'side' => 'any', 'prompt' => "Give_a_unit_-{$n}/-{$n}_for_this_phase",
            ]);
};
