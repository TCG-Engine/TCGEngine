<?php
// SHD_164
// Cost 2 - Rhokai Gunship - [Aggression] - Power 2 - HP 1
// Text: When Defeated: Deal 1 damage to a unit or base.

// ─── SHD_164 Rhokai Gunship ───────────────────────────────────────────────────
// When Defeated: Deal 1 damage to a unit or base (mandatory; DEAL_TARGET handles unit-or-base).
$whenDefeatedAbilities["SHD_164:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = _SWUAllUnitsAndBases(intval($player));
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Deal_1_to_a_unit_or_base", "DEAL_TARGET|1");
};
