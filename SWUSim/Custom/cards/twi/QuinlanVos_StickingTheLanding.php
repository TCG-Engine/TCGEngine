<?php
// TWI_018
// Cost 5 - Quinlan Vos - Sticking the Landing - [Cunning,Heroism] - Power 3 - HP 7
// Text: When you play a unit: You may exhaust this leader. If you do, deal 1 damage to an enemy unit that costs the same as the played unit.
// DeployText: When you play a unit: You may deal 1 damage to an enemy unit that costs the same as or less than the played unit.
// Epic Action: If you control 5 or more resources, deploy this leader.

$customDQHandlers["TWI_018#0"] = function ($player, $parts, $lastDecision) {
  if (SWUDecisionDeclined($lastDecision))
    return;
  global $playerID;
  $playerID = intval($player);
  // Exhaust the leader (the front-side cost) then deal 1.
  $leaderArr = &GetLeader(intval($player));
  if (!empty($leaderArr) && !empty($leaderArr[0]))
    $leaderArr[0]->Ready = false;
  SWUDealDamageToUnit($lastDecision, 1, intval($player));
};

function Twi018Reaction(int $player, int $playedCost, bool $isFront): void
{
  global $playerID;
  $playerID = intval($player);
  $targets = [];
  foreach (["theirGroundArena", "theirSpaceArena"] as $z) {
    foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
      $o = GetZoneObject($mz);
      if (SWUObjGone($o))
        continue;
      $c = intval(CardCost($o->CardID ?? ''));
      if ($isFront ? ($c === $playedCost) : ($c <= $playedCost))
        $targets[] = $mz;
    }
  }
  if (empty($targets))
    return;
  // Front side exhausts the leader as the cost; deployed has no cost. Offer a may-choose either way.
  $tag = $isFront ? "TWI_018#0" : "DEAL_UNIT_DAMAGE|1";
  SWUQueueMayChooseTarget(intval($player), $targets, "Deal_1_damage_to_a_qualifying_enemy_unit?", "Choose_an_enemy_unit", $tag);
}
