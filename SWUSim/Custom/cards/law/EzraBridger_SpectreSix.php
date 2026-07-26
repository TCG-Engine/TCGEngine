<?php
// LAW_035
// Cost 4 - Ezra Bridger - Spectre Six - [Vigilance,Command,Heroism] - Power 4 - HP 5
// Text: Raid 1 (This unit gets +1/+0 while attacking.) / When Played: You may heal 2 damage from a unit. If you control a Aggression or Cunning unit, you may heal 4 damage from a unit instead.

// LAW_035 Ezra Bridger — When Played: heal 2 from a unit (4 instead if you control an Aggression or
// Cunning unit). "You may heal."
$whenPlayedAbilities["LAW_035:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $amount = (PlayerHasUnitWithAspectInPlay(intval($player), 'Aggression') || PlayerHasUnitWithAspectInPlay(intval($player), 'Cunning')) ? 4 : 2;
    $units = SWUAllUnits();
    if (empty($units)) return;
    SWUQueueMayChooseTarget(intval($player), $units, "Heal_{$amount}_from_a_unit?", "Choose_a_unit", "HEAL_TARGET|{$amount}");
};
