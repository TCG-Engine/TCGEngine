<?php
// JTL_132
// Cost 1 - First Order Stormtrooper - [Aggression,Villainy] - Power 2 - HP 1
// Text: On Attack/When Defeated: Deal 1 indirect damage to a player. (They assign 1 unpreventable damage among their base and units.)

// ── JTL_132 First Order Stormtrooper — On Attack/When Defeated: 1 indirect damage to a player. ─────────
$jtl132_indirect = function ($player, $mzID) {
  global $playerID;
  $playerID = intval($player);
  SWUDealIndirectToChosenPlayer(intval($player), 1, '', _SWUSrcUID($mzID));
};

$onAttackAbilities["JTL_132:0"] = $jtl132_indirect;

$whenDefeatedAbilities["JTL_132:0"] = $jtl132_indirect;
