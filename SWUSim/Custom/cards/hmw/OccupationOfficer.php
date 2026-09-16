<?php
// HMW_242 Occupation Officer — Unit (Ground) 3/2, cost 2, [Villainy], Imperial.
// "When Played: If you control 6 or more resources, you may give a Weakness token to a unit."
// "control" counts every resource, ready or exhausted (paying for the Officer does not lower it).

$whenPlayedAbilities["HMW_242:0"] = function($player, $mzID = '') {
    if (SWUResourceCount(intval($player)) < 6) return;
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'GIVE_WEAKNESS',
        'may'          => true,
        'question'     => 'Give_a_Weakness_token_to_a_unit?',
        'prompt'       => 'Choose_a_unit',
    ]);
};
