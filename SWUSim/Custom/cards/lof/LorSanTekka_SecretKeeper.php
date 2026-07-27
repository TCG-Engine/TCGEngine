<?php
// LOF_095
// Cost 2 - Lor San Tekka - Secret Keeper - [Command,Heroism] - Power 3 - HP 2
// Text: When Defeated: You may give an Experience token to a <uq> (unique) unit.

// LOF_095 Lor San Tekka — When Defeated: may give an Experience token to a unique unit.
$whenDefeatedAbilities["LOF_095:0"] = function($player, $mzID) {
    // A unique unit (either player).
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'GIVE_EXPERIENCE', 'may' => true,
        'extraFilter' => fn($o) => CardUnique($o->CardID ?? ''),
        'question' => "Give_Exp_to_a_unique_unit?",
        'prompt'   => "Choose_a_unique_unit",
    ]);
};
