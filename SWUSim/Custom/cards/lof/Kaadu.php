<?php
// LOF_114
// Cost 4 - Kaadu - [Command] - Power 4 - HP 4
// Text: When Played: You may give another friendly unit Overwhelm for this phase. (When attacking an enemy unit, deal excess damage to the opponent's base.)

// LOF_114 Kaadu — When Played: may give another friendly unit Overwhelm for this phase.
$whenPlayedAbilities["LOF_114:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, ['continuation'=>'GRANT_PHASE_KEYWORD|OVERWHELM^LOF_114', 'side'=>'friendly', 'excludeSelf'=>true, 'may'=>true, 'question'=>"Give_another_friendly_unit_Overwhelm?", 'prompt'=>"Choose_a_unit"]);
};
