<?php
// SEC_031
// Cost 3 - Nute Gunray - Escaping Justice - [Vigilance,Villainy] - Power 3 - HP 4
// Text: Grit / When Played/On Attack: You may give another friendly Official unit Sentinel for this phase.

// ── SEC Phase 6: Buff / debuff ───────────────────────────────────────────────
// SEC_031 Nute Gunray — Grit (auto) + When Played / On Attack: may give ANOTHER friendly Official unit
// Sentinel for this phase.
$sec031 = function ($player, $mzID) {
  global $playerID;
  $playerID = intval($player);
  SWUOfferUnitTarget($player, $mzID, ['continuation'=>'GRANT_PHASE_KEYWORD|SENTINEL^SEC_031', 'side'=>'my', 'traits'=>['Official'], 'excludeSelf'=>true, 'may'=>true, 'question'=>"Give_another_Official_unit_Sentinel?", 'prompt'=>"Choose_an_Official_unit"]);
};

$whenPlayedAbilities["SEC_031:0"] = $sec031;

$onAttackAbilities["SEC_031:0"] = $sec031;
