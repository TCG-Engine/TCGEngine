<?php
// JTL_240
// Cost 4 - Fett's Firespray - Feared Silhouette - [Villainy] - Power 4 - HP 4
// Text: When Played/On Attack: Deal 1 indirect damage to a player. If you control Boba Fett (as a unit, upgrade, or leader), deal 2 indirect damage instead. (They assign unpreventable damage among their base and units.)

// ── JTL_240 Fett's Firespray — When Played/On Attack: 1 indirect to a player (2 if you control Boba Fett).
$jtl240_indirect = function ($player, $mzID) {
  global $playerID;
  $playerID = intval($player);
  $amt = _SWUControlsTitle(intval($player), ['Boba Fett']) ? 2 : 1;
  SWUDealIndirectToChosenPlayer(intval($player), $amt, '', _SWUSrcUID($mzID));
};

$whenPlayedAbilities["JTL_240:0"] = $jtl240_indirect;

$onAttackAbilities["JTL_240:0"] = $jtl240_indirect;
