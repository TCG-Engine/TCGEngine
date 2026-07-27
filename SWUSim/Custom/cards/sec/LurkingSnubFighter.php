<?php
// SEC_189
// Cost 3 - Lurking Snub Fighter - [Cunning,Villainy] - Power 2 - HP 3
// Text: When Played: You may exhaust a unit. / Plot (When you deploy a leader, you may play this card from your resources, paying its cost. Replace it with the top card of your deck.)

// SEC_189 Lurking Snub Fighter — When Played: you may exhaust a unit.
$whenPlayedAbilities["SEC_189:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'EXHAUST_UNIT', 'may' => true,
        'question' => "Exhaust_a_unit?", 'prompt' => "Choose_a_unit",
    ]);
};
