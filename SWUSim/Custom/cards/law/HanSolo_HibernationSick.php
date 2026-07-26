<?php
// LAW_037
// Cost 1 - Han Solo - Hibernation Sick - [Vigilance,Command] - Power 1 - HP 1
// Text: Shielded (When you play this unit, give a Shield token to him.) / On Attack: Give an Experience token to this unit.

// LAW_037 Han Solo — Shielded + On Attack: give an Experience token to this unit. (No target choice.)
$onAttackAbilities["LAW_037:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    DoGiveExperienceToken(intval($player), $mzID);
};
