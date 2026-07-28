<?php
// SHD_014
// Cost 6 - Cad Bane - He Who Needs No Introduction - [Cunning,Villainy] - Power 2 - HP 8
// Text: When you play an Underworld card: You may exhaust this leader. If you do, an opponent chooses a unit they control. Deal 1 damage to it.
// DeployText: Raid 2 (This unit gets +2/+0 while attacking.) / When you play an Underworld card: You may choose an opponent. They choose a unit they control. Deal 2 damage to it. Use this ability only once each round.
// Epic Action: If you control 6 or more resources, deploy this leader.

$customDQHandlers["SHD_014#exhaust"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (($lastDecision ?? '') !== 'YES') return;
    $leaderArr = &GetLeader(intval($player));
    foreach ($leaderArr as &$l) { if (($l->CardID ?? '') === 'SHD_014' && empty($l->removed)) { $l->Ready = false; break; } }  // exhaust the leader (cost)
    unset($l);
    $opp = OtherPlayer(intval($player));
    $playerID = $opp;   // the opponent chooses among THEIR own units ("my..." from their frame)
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) { $o = GetZoneObject($mz); if ($o !== null && empty($o->removed)) $targets[] = $mz; }
    }
    if (empty($targets)) return;
    DecisionQueueController::AddDecision($opp, 'MZCHOOSE', implode('&', $targets), 1, tooltip:"Choose_a_unit_you_control");
    DecisionQueueController::AddDecision($opp, 'CUSTOM', "SHD_014#0|{$player}", 1);
    // leave $playerID = $opp so MZCountChoices resolves the relative mzIDs under the opponent
};

$customDQHandlers["SHD_014#0"] = function($opp, $parts, $lastDecision) {
    global $playerID;
    $caster = intval($parts[0] ?? OtherPlayer(intval($opp)));
    if (SWUDecisionDeclined($lastDecision)) return;
    $playerID = intval($opp);                        // resolve the chosen mzID in the opponent's frame → UID
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $uid = intval($o->UniqueID ?? 0);
    $playerID = $caster;
    $mz = SWUFindMzByUID($uid);                       // UID is frame-independent
    if ($mz !== null) SWUDealDamageToUnit($mz, 1, $caster);
};

function Shd014FrontReaction($player): void
{
  global $playerID;
  $playerID = intval($player);
  if (!_SWULeaderReadyUndeployed(intval($player), 'SHD_014'))
    return;
  $opp = OtherPlayer(intval($player));
  $playerID = $opp;   // resolve "my..." as the opponent's own board to test for targets
  $oppUnits = array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter));
  $playerID = intval($player);
  if (empty($oppUnits))
    return;   // no enemy unit to damage → don't bother offering the exhaust
  DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Exhaust_Cad_Bane_to_deal_1_to_an_opponent's_unit?");
  DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SHD_014#exhaust", 1);
}
