<?php
// HMW_165 Commandeered Tour Shuttle — Unit (Space) 2/2, cost 3, [Aggression][Heroism], Rebel/Vehicle/Transport.
// "When Played: You may ready another unit with 3 or less power."
// "another" excludes the Shuttle by UniqueID; "a unit" is unqualified, so an enemy unit is legal too.
// An already-ready unit is a legal (pointless) pick — the text does not say "exhausted".

$whenPlayedAbilities["HMW_165:0"] = function($player, $mzID = '') {
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'READY_UNIT',
        'excludeSelf'  => true,
        'may'          => true,
        'extraFilter'  => fn($o) => intval(ObjectCurrentPower($o)) <= 3,
        'question'     => 'Ready_another_unit_with_3_or_less_power?',
        'prompt'       => 'Choose_a_unit_to_ready',
    ]);
};
