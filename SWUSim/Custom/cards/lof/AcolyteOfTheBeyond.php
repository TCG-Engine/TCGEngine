<?php
// LOF_129
// Cost 2 - Acolyte of the Beyond - [Aggression,Villainy] - Power 2 - HP 3
// Text: On Attack/When Defeated: The Force is with you (create your Force token).

// LOF_129 Acolyte of the Beyond — On Attack AND When Defeated.
$onAttackAbilities["LOF_129:0"]   =
$whenDefeatedAbilities["LOF_129:0"] = function($player, $mzID) { TheForceIsWithYou(intval($player)); };
