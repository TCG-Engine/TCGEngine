<?php
// SOR_160
// Cost 2 - Wolffe - Suspicious Veteran - [Aggression] - Power 3 - HP 2
// Text: Saboteur (When this unit attacks, ignore Sentinel and defeat the defender's Shields.) / When Played/On Attack: Bases can't be healed for this phase.

// SOR_160 Wolffe — When Played / On Attack: "Bases can't be healed for this phase." A global lock
// (affects all bases); checked in OnHealBase, cleared at RegroupPhaseStart. No decision/target.
$whenPlayedAbilities["SOR_160:0"] = $onAttackAbilities["SOR_160:0"] = function($player, $mzID) {
    AddGlobalEffects(intval($player), 'SWU_NOHEAL_BASE');
};
