<?php
// HMW_059
// Cost 2 - Clone X Assassin - [Vigilance][Villainy] - Unit (Ground) 1/3 - Traits: Imperial, Clone, Trooper
// Text: When Defeated: You may give a Weakness token to a unit.
//
// "a unit" carries no friendly/enemy qualifier → any unit is a legal target (friendlyOnly:false). The
// Weakness token (HMW_T02) is a -1/-1 Token Upgrade; its stat modifier flows through the normal upgrade
// stat loop (no bespoke stat code), and GIVE_WEAKNESS runs a shrink-defeat sweep so a unit reduced to 0
// remaining HP is defeated.
$whenDefeatedAbilities["HMW_059:0"] = function($player, $mzID = '') {
    GiveTokenUpgrade(intval($player), $mzID, [
        'token'        => 'WEAKNESS',
        'friendlyOnly' => false,
        'may'          => true,
        'prompt'       => 'Give_a_Weakness_token_to_a_unit',
    ]);
};
