<?php
// SHD_171
// Cost 6 - Covetous Rivals - [Aggression] - Power 5 - HP 5
// Text: Grit (This unit gets +1/+0 for each damage on it.) / When Played/On Attack: You may deal 2 damage to a unit with a Bounty.

// ─── SHD_171 Covetous Rivals ──────────────────────────────────────────────────
// Grit (auto) + When Played / On Attack: You may deal 2 damage to a unit with a Bounty. MZMAYCHOOSE is
// OnAttack-safe; filter to units that have the Bounty keyword.
$shd171CovetousRivals = function ($player, $mzID) {
  global $playerID;
  $playerID = intval($player);
  $targets = [];
  foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
    foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
      $o = GetZoneObject($mz);
      if ($o !== null && empty($o->removed) && ObjectHasBounty($o) > 0)
        $targets[] = $mz;
    }
  }
  SWUQueueMayChooseTarget(
    intval($player),
    $targets,
    "Deal_2_to_a_unit_with_a_Bounty?",
    "Deal_2_to_a_Bounty_unit",
    "DEAL_UNIT_DAMAGE|2"
  );
};

$whenPlayedAbilities["SHD_171:0"] = $shd171CovetousRivals;

$onAttackAbilities["SHD_171:0"] = $shd171CovetousRivals;
