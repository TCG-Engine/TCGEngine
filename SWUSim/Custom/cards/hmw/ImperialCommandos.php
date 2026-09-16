<?php
// HMW_068 Imperial Commandos — Unit (Ground) 4/4, cost 6, [Vigilance][Villainy], Imperial/Clone/Trooper.
// "When Played: You may defeat a non-leader unit with 4 or less power."
// HMW_102 Dragon's Might's clause, made optional. "a non-leader unit" is unqualified, so either side —
// and the Commandos themselves (4 power) are a legal pick. Power is CURRENT power (buffs/Weakness count).

$whenPlayedAbilities["HMW_068:0"] = function($player, $mzID = '') {
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'DEFEAT_UNIT',
        'nonLeader'    => true,
        'may'          => true,
        'extraFilter'  => fn($o) => intval(ObjectCurrentPower($o)) <= 4,
        'question'     => 'Defeat_a_non-leader_unit_with_4_or_less_power?',
        'prompt'       => 'Choose_a_unit_to_defeat',
    ]);
};
