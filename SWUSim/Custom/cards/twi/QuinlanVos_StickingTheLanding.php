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
  // ⚠ "THIS leader" is the Quinlan instance, NOT `GetLeader($player)[0]`. In a Twin Suns seat with
  // Quinlan in the second slot the old index-0 write taxed the OTHER leader and left Quinlan ready, so
  // the ability re-armed on the next unit played. The trigger gate was already CardID-keyed
  // (_SWULeaderReadyUndeployed in GameLogic), so the offer was correct and only the bill went astray.
  // Leader CardIDs are unique per seat (CR 12.3 forbids copies), which is what makes the CardID the
  // instance key here — same reasoning as SWUFindLeaderByCardID.
  // Re-checked at RESOLUTION, not just at the offer: "IF YOU DO" — a leader that was exhausted or
  // deployed between the trigger and the answer can no longer pay, and then no damage is dealt.
  if (!_SWULeaderReadyUndeployed(intval($player), 'TWI_018'))
    return;
  _SWUExhaustUndeployedLeader(intval($player), 'TWI_018');
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
