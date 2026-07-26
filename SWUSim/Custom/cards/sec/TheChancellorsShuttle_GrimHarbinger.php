<?php
// SEC_027
// Cost 2 - The Chancellor's Shuttle - Grim Harbinger - [Vigilance,Villainy] - Power 1 - HP 3
// Text: Restore 1 / When Defeated: If you control Chancellor Palpatine (as a leader or unit), you may give an Experience token to a unit.

// SEC_027 The Chancellor's Shuttle — Restore 1 (auto) + When Defeated: if you control Chancellor
// Palpatine (leader or unit), you may give an Experience token to a unit.
$whenDefeatedAbilities["SEC_027:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (!_SWUControlsTitle(intval($player), ['Chancellor Palpatine'])) return;
    $units = SWUAllUnits();
    if (empty($units)) return;
    SWUQueueMayChooseTarget(intval($player), $units, "Give_an_Experience_token_to_a_unit?", "Choose_a_unit", "GIVE_EXPERIENCE|1");
};
