<?php
// LAW_086
// Cost 5 - The Stranger - No Survivors - [Cunning,Vigilance,Villainy] - Power 1 - HP 7
// Text: Ambush / Grit / While attacking, you may have the defending unit deal combat damage before this unit.

// LAW_086 The Stranger — resolve the "have the defending unit deal combat damage first?" offer. YES
// marks the attacker with DEFENDER_FIRST (attack-duration), which SWUCombatDamage reads to reverse the
// combat-damage order (defender first, then the attacker's Grit-boosted hit).
// The stored mzID was minted in the ATTACKER's frame ("myGroundArena-N"), so resolve it there: with $playerID
// left at another seat the marker landed on that seat's unit, and a seat-2 Stranger's YES did nothing.
$customDQHandlers["LAW_086#0"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $playerID = intval($player);
  if ($lastDecision !== 'YES')
    return;
  $mzID = $parts[0] ?? '';
  if ($mzID !== '')
    AddTurnEffect($mzID, 'DEFENDER_FIRST');
};
