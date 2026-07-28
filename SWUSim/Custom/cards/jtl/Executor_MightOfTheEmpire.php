<?php
// JTL_090
// Cost 11 - Executor - Might of the Empire - [Command,Villainy] - Power 12 - HP 12
// Text: Overwhelm / When Played/On Attack/When Defeated: Create 3 TIE Fighter tokens.

// ── JTL_090 Executor — When Played/On Attack/When Defeated: Create 3 TIE Fighter tokens. ──────────────
$jtl090_3tie = function ($player, $mzID) {
  global $playerID;
  $playerID = intval($player);
  SWUCreateUnitTokens(intval($player), 'JTL_T01', 3);
};

$whenPlayedAbilities["JTL_090:0"] = $jtl090_3tie;

$onAttackAbilities["JTL_090:0"] = $jtl090_3tie;

$whenDefeatedAbilities["JTL_090:0"] = $jtl090_3tie;
