<?php
// SEC_034
// Cost 5 - Cad Bane - Impressed Now? - [Vigilance,Villainy] - Power 4 - HP 5
// Text: When Played: You may defeat a unit with 2 or less remaining HP. / Plot (When you deploy a leader, you may play this card from your resources, paying its cost. Replace it with the top card of your deck.)

// SEC_034 Cad Bane — When Played: you may defeat a unit with 2 or less remaining HP. Fires on every
// play of Cad Bane, including when he is played via Plot.
$whenPlayedAbilities["SEC_034:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'DEFEAT_UNIT', 'may' => true,
        'extraFilter' => fn($o) => intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0) <= 2,
        'question' => "Defeat_a_unit_with_2_or_less_HP?", 'prompt' => "Defeat_a_unit_with_2_or_less_remaining_HP",
    ]);
};
