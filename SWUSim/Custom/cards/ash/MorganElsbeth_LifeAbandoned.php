<?php
// ASH_050
// Cost 6 - Morgan Elsbeth - Life Abandoned - [Vigilance,Villainy] - Power 5 - HP 6
// Text: Support (When you play this unit, you may attack with another unit. It gains this unit's other abilities for this attack.) / When Defeated: You may give a unit -2/-2 for this phase.

// ASH_050 Morgan Elsbeth — When Defeated: you may give a unit -2/-2 for this phase.
//
// ⚠ HER SUPPORT *DOES* LEND THIS. An older comment here claimed the opposite ("NOT lent by Support;
// fires only on Morgan's own defeat") and it was simply wrong: Support reads "it gains this unit's OTHER
// abilities for this attack", and the engine implements exactly that — if the unit she attacked with is
// defeated during that attack while bearing her SUPPORT_GRANT marker, the defeat collector fires a
// 'SupportWhenDefeated' trigger carrying HER CardID against THAT unit's mzID, which lands in this same
// closure. So this ability runs for two different defeats: Morgan's own, and the supported attacker's.
// JTL_002 Thrawn can re-use it in both cases (fixed 2026-09-27, game 1400002); the lent case is pinned by
// jtl/ThrawnReuse_TwoWhenDefeatedAbilities.md.
$whenDefeatedAbilities["ASH_050:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'APPLY_PHASE_DEBUFF|2|2|ASH_050', 'side' => 'any', 'may' => true,
        'question' => "Give_a_unit_-2/-2_this_phase?", 'prompt' => "Choose_a_unit",
    ]);
};
