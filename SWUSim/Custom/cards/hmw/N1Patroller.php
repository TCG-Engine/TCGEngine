<?php
// HMW_086 N-1 Patroller — Unit (Space) 2/2, cost 3, [Vigilance], Naboo/Vehicle/Fighter.
// "When Played: You may defeat a non-leader unit with 1 or less remaining HP."
// LAW_004 Aurra Sing's remaining-HP reading: current max HP minus damage. Either side; the Patroller
// itself enters undamaged at 2, so it only qualifies if something already shrank it.

$whenPlayedAbilities["HMW_086:0"] = function($player, $mzID = '') {
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'DEFEAT_UNIT',
        'nonLeader'    => true,
        'may'          => true,
        'extraFilter'  => fn($o) => intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0) <= 1,
        'question'     => 'Defeat_a_non-leader_unit_with_1_or_less_remaining_HP?',
        'prompt'       => 'Choose_a_unit_to_defeat',
    ]);
};
