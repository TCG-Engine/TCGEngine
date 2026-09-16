<?php
// HMW_097 Dire Prowess — Upgrade +1/+1, cost 2, [Vigilance], Learned. Attach to a unit (any unit).
// "When Played: You may give a Weakness token to a unit."
// A non-pilot upgrade's When Played dispatches with the HOST as $mzID; the host is itself a legal target.
// GIVE_WEAKNESS runs the 0-HP sweep, so a Weakness that shrinks a unit to nothing defeats it here.

$whenPlayedAbilities["HMW_097:0"] = function($player, $mzID = '') {
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'GIVE_WEAKNESS',
        'may'          => true,
        'question'     => 'Give_a_Weakness_token_to_a_unit?',
        'prompt'       => 'Choose_a_unit',
    ]);
};
