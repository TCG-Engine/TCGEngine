<?php
// LAW_055
// Cost 2 - Chopper - Spectre Three - [Command,Aggression,Heroism] - Power 1 - HP 2
// Text: Raid 1 (This unit gets +1/+0 while attacking.) / When Played: Give an Experience token to this unit. If you control a Cunning or Vigilance unit, give 2 Experience tokens to him instead.

// LAW_055 Chopper — When Played: give an Experience token to this unit (2 instead if you control a
// Cunning or Vigilance unit).
$whenPlayedAbilities["LAW_055:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $n = (PlayerHasUnitWithAspectInPlay(intval($player), 'Cunning') || PlayerHasUnitWithAspectInPlay(intval($player), 'Vigilance')) ? 2 : 1;
    for ($i = 0; $i < $n; $i++) DoGiveExperienceToken(intval($player), $mzID);
};
