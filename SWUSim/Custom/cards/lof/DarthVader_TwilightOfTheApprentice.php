<?php
// LOF_037
// Cost 6 - Darth Vader - Twilight of the Apprentice - [Vigilance,Villainy] - Power 5 - HP 6
// Text: When Played: Give a Shield token to a friendly unit and to an enemy unit. / On Attack: Defeat an enemy unit with a Shield token on it.

// LOF_037 Darth Vader — When Played: Shield a friendly AND an enemy unit. On Attack: defeat an enemy
// unit with a Shield token on it.
$whenPlayedAbilities["LOF_037:0"] = function($player, $mzID) {
    GiveTokenUpgrade($player, $mzID, ['token'=>'SHIELD','prompt'=>"Give_a_Shield_to_a_friendly_unit"]);
    SWUOfferUnitTarget($player, $mzID, ['continuation'=>'GIVE_SHIELD','side'=>'their','prompt'=>"Give_a_Shield_to_an_enemy_unit"]);
};

$onAttackAbilities["LOF_037:0"] = function($player, $mzID) {
    // MAY (not mandatory): a mandatory multi-target choose auto-skips inside OnAttack.
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'DEFEAT_UNIT', 'side' => 'their', 'may' => true,
        'extraFilter' => fn($o) => _SWUCountShieldSubcards($o) > 0,
        'question' => "Defeat_an_enemy_unit_with_a_Shield?", 'prompt' => "Choose_a_shielded_enemy",
    ]);
};
