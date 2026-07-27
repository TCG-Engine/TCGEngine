<?php
// TS26_75
// Cost 5 - Jango Fett - Guarding the Count - [Cunning,Villainy] - Power 5 - HP 5
// Text: While an enemy unit has attacked your base this phase, this unit gains Ambush. (When you play this unit, he may attack an enemy unit.) / On Attack: Give an enemy unit -3/-0 for this phase.

// TS26_75 Jango Fett — On Attack: give an enemy unit -3/-0 for this phase. (MZMAYCHOOSE — the in-combat
// safe choose; a mandatory OnAttack MZCHOOSE auto-skips.)
$onAttackAbilities["TS26_75:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'APPLY_PHASE_DEBUFF|3|0|TS26_75', 'side' => 'their',
        'may' => true, 'prompt' => "Choose_an_enemy_unit", 'question' => "Give_an_enemy_unit_-3/-0?",
    ]);
};
