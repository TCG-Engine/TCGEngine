<?php
// SEC_059
// Cost 2 - Senate Warden - [Vigilance] - Power 2 - HP 2
// Text: When Defeated: You may disclose Vigilance (reveal a card from your hand with this aspect icon). If you do, give an Experience token to a unit.

// SEC_059 Senate Warden — When Defeated: you may disclose Vigilance → give an Experience token to a unit.
$whenDefeatedAbilities["SEC_059:0"] = function($player, $mzID) {
    SWUQueueDisclose(intval($player), ['Vigilance'], "SEC_059#0", "Disclose_Vigilance_to_give_an_Experience_token");
};

$customDQHandlers["SEC_059#0"] = function ($player, $parts, $lastDecision) {
  GiveTokenUpgrade(intval($player), '', [
    'friendlyOnly' => false,
    'prompt'       => "Give_an_Experience_token_to_a_unit",
  ]);
};
