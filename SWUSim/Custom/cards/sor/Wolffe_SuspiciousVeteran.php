<?php
// SOR_160
// Cost 2 - Wolffe - Suspicious Veteran - [Aggression] - Power 3 - HP 2
// Text: Saboteur (When this unit attacks, ignore Sentinel and defeat the defender's Shields.) / When Played/On Attack: Bases can't be healed for this phase.

// SOR_160 Wolffe — When Played / On Attack: "Bases can't be healed for this phase." A global lock
// (affects all bases); checked in OnHealBase, cleared at RegroupPhaseStart. No decision/target.
$whenPlayedAbilities["SOR_160:0"] = $onAttackAbilities["SOR_160:0"] = function($player, $mzID) {
    AddGlobalEffects(intval($player), 'SWU_NOHEAL_BASE');
    // Companion marker so a blocked heal can name what stopped it — see LAW_197 Shifty Suspects, which
    // shares this flag. Exact-match counting hides it from the lock check; the PREFIX clear sweeps it.
    AddGlobalEffects(intval($player), 'SWU_NOHEAL_BASE_SRC_SOR_160');
};
