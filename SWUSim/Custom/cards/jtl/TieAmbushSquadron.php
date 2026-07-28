<?php
// JTL_087
// Cost 4 - TIE Ambush Squadron - [Command,Villainy] - Power 2 - HP 3
// Text: Ambush (When you play this unit, it may attack an enemy unit.) / When Played/When Defeated: Create a TIE Fighter token.

// ── JTL_087 TIE Ambush Squadron — When Played/When Defeated: Create a TIE Fighter token. ──────────────
$jtl087_tie = function ($player, $mzID) {
  global $playerID;
  $playerID = intval($player);
  SWUCreateUnitToken(intval($player), 'JTL_T01'); // TIE Fighter (Space, 1/1)
};

$whenPlayedAbilities["JTL_087:0"] = $jtl087_tie;

$whenDefeatedAbilities["JTL_087:0"] = $jtl087_tie;
