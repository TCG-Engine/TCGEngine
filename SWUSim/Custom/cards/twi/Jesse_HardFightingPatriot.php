<?php
// TWI_145
// Cost 3 - Jesse - Hard-Fighting Patriot - [Aggression,Heroism] - Power 4 - HP 4
// Text: Raid 1 (This unit gets +1/+0 while attacking.) / When Played: An opponent creates 2 Battle Droid tokens.

// TWI_145 Jesse — "When Played: An opponent creates 2 Battle Droid tokens." (Raid 1 is a keyword.)
$whenPlayedAbilities["TWI_145:0"] = function($player, $mzID) {
    SWUCreateUnitTokens(OtherPlayer(intval($player)), 'TWI_T01', 2); // opponent's Battle Droids
};
