<?php
// LOF_101
// Cost 8 - Yoda - My Ally is the Force - [Command,Heroism] - Power 5 - HP 9
// Text: When Played: You may use the Force. If you do, heal 5 damage from a base. / When you use the Force: You may deal damage to a unit equal to twice the number of units you control.

// ── Phase 15 — "When you use the Force" reactive window (collected in _SWUQueueUseForceReactions) ──────
// LOF_101 Yoda — When Played: You may use the Force. If you do, heal 5 damage from a base. (The use-Force
// reaction below ALSO fires from this, via UseTheForce.)
$whenPlayedAbilities["LOF_101:0"] = function($player, $mzID) {
    SWUQueueMayUseTheForce(intval($player), "Yoda:_use_the_Force_to_heal_5_damage_from_a_base?", "LOF_101#1");
};

$customDQHandlers["LOF_101#1"] = function ($player, $parts, $lastDecision) {
  if ($lastDecision !== 'YES')
    return;
  global $playerID;
  $playerID = intval($player);
  UseTheForce(intval($player));            // pay the Force (also triggers Yoda's own use-Force reaction)
  OnHealBase(intval($player), intval($player), 5);  // heal 5 from your base
};

// LOF_101 use-Force reaction — may deal damage to a unit equal to twice the units you control.
$customDQHandlers["LOF_101#0"] = function ($player, $parts, $lastDecision) {
  if ($lastDecision !== 'YES')
    return;
  global $playerID;
  $playerID = intval($player);
  $n = 2 * count(GetUnitsInPlay(intval($player)));
  if ($n <= 0)
    return;
  SWUOfferUnitTarget(intval($player), '', [
      'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => $n,
      'prompt' => "Deal_{$n}_damage_to_a_unit",
  ]);
};
