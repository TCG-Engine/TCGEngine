<?php
// HMW_092 Starlit Purrgil — Unit (Space) 4/5, cost 6, [Vigilance], Creature.
// "Restore 2. When Played: You may exhaust a unit."
// Restore is keyword-wired. The When Played is SEC_189 Lurking Snub Fighter's text: any unit, the
// Purrgil itself included (it enters exhausted, so picking it changes nothing).

$whenPlayedAbilities["HMW_092:0"] = function($player, $mzID = '') {
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'EXHAUST_UNIT',
        'may'          => true,
        'question'     => 'Exhaust_a_unit?',
        'prompt'       => 'Choose_a_unit_to_exhaust',
    ]);
};
