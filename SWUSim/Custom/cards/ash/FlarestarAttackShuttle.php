<?php
// ASH_167
// Cost 2 - Flarestar Attack Shuttle - [Aggression] - Power 2 - HP 1
// Text: When Played/When Defeated: You may give an Advantage token to a unit.

// ── ASH Phase 2 — Advantage-token givers ──
// ASH_167 Flarestar Attack Shuttle — When Played/When Defeated: you may give an Advantage token to a unit.
$whenPlayedAbilities["ASH_167:0"] = $whenDefeatedAbilities["ASH_167:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $tg = SWUAllUnits();
    if (empty($tg)) return;
    SWUQueueMayChooseTarget(intval($player), $tg, "Give_an_Advantage_token_to_a_unit?", "Choose_a_unit", "GIVE_ADVANTAGE");
};
