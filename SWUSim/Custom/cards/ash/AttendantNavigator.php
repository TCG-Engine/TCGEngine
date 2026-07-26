<?php
// ASH_238
// Cost 2 - Attendant Navigator - [Villainy] - Power 2 - HP 3
// Text: When Played: You may give 2 Advantage tokens to a space unit.

// ── ASH Phase 2 Batch 2.7 ──
// ASH_238 Attendant Navigator — When Played: you may give 2 Advantage tokens to a space unit.
$whenPlayedAbilities["ASH_238:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $tg = SWUAllUnits(null, SpaceArena);
    if (empty($tg)) return;
    SWUQueueMayChooseTarget(intval($player), $tg, "Give_2_Advantage_tokens_to_a_space_unit?", "Choose_a_space_unit", "GIVE_ADVANTAGE|2");
};
