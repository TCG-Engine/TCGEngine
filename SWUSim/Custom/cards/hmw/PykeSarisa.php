<?php
// HMW_246 Pyke Sarisa — Unit (Space) 4/3, cost 4, [Villainy], Underworld/Vehicle/Transport.
// "When Played: Give a unit Sentinel for this phase."
// Mandatory; any unit, the Sarisa itself included. SOR_086 Gladiator Star Destroyer's text — the grant
// row lives in $turnEffectRegistry under HMW_246 so the Active Effects popup shows this card.

$whenPlayedAbilities["HMW_246:0"] = function($player, $mzID = '') {
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'GRANT_PHASE_KEYWORD|HMW_246',
        'prompt'       => 'Give_a_unit_Sentinel_for_this_phase',
    ]);
};
