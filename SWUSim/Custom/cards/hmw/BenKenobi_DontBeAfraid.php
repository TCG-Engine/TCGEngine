<?php
// HMW_261 Ben Kenobi — Unit (Ground) 5/5, cost 5.
// "When Played: You may exhaust a unit with 3 or less power. On Attack: You may heal 3 damage from another unit."
// Both targets are either side. Ben (5 power) is never an exhaust target; "another" excludes him from the heal.

$whenPlayedAbilities["HMW_261:0"] = function($player, $mzID = '') {
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'EXHAUST_UNIT', 'may' => true,
        'extraFilter'  => fn($o) => intval(ObjectCurrentPower($o)) <= 3,
        'question' => 'Exhaust_a_unit_with_3_or_less_power?', 'prompt' => 'Choose_a_unit_to_exhaust',
    ]);
};

$onAttackAbilities["HMW_261:0"] = function($player, $mzID = '') {
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'HEAL_TARGET', 'amount' => 3, 'excludeSelf' => true, 'may' => true,
        'question' => 'Heal_3_damage_from_another_unit?', 'prompt' => 'Choose_a_unit_to_heal',
    ]);
};
