<?php
// SEC_055
// Cost 1 - Dhani Pilgrim - [Vigilance] - Power 1 - HP 3
// Text: When Played/When Defeated: Heal 1 damage from your base.

// SEC_055 Dhani Pilgrim — When Played / When Defeated: heal 1 damage from your base.
$whenPlayedAbilities["SEC_055:0"]   = function($player, $mzID) { OnHealBase(intval($player), intval($player), 1); };

$whenDefeatedAbilities["SEC_055:0"] = function($player, $mzID) { OnHealBase(intval($player), intval($player), 1); };
