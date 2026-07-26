<?php
// LAW_056
// Cost 4 - Cassian Andor - Everything For the Rebellion - [Command,Aggression,Heroism] - Power 4 - HP 4
// Text: When a friendly unit's attack ends: If the defending unit was defeated, deal 2 damage to a base.

// ─── Twin Suns (Group B): choose-an-opponent continuations ────────────────────────────────────────────
// These run after SWUQueueChooseOpponent (GameLogic.php) resolves; the picked seat is in $lastDecision as
// "P{n}" (parse via SWUPickedOpponent). Reached ONLY on the N-player branch — 2-player keeps its inline path.
// LAW_056 Cassian Andor (field passive) — deal 2 to the CHOSEN opponent's base (fired per Cassian).
$customDQHandlers["LAW_056#0"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $playerID = intval($player);
  $opp = SWUPickedOpponent($lastDecision);
  if ($opp > 0)
    SWUDealDamageToBase(2, $opp);
};
