<?php
// SHD_142
// Cost 7 - Pre Vizsla - Power Hungry - [Villainy,Aggression] - Power 8 - HP 7
// Text: When Played/On Attack: You may pay the cost of an upgrade attached to another non-Vehicle unit. If you do, take control of that upgrade and attach it to this unit, if able. If it can't attach to this unit, defeat it instead.

$whenPlayedAbilities["SHD_142:0"] = function($player, $mzID) { PreVizslaPowerHungryOffer(intval($player), $mzID); };

$onAttackAbilities["SHD_142:0"]   = function($player, $mzID) { PreVizslaPowerHungryOffer(intval($player), $mzID); };

$customDQHandlers["SHD_142#move"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $playerID = intval($player);
  $drain = function () use ($player) {
    $t = &GetTempZone($player);
    while (count($t) > 0)
      array_pop($t); };
  if (SWUDecisionDeclined($lastDecision)) {
    $drain();
    return;
  }
  if (!preg_match('/-(\d+)$/', $lastDecision, $m)) {
    $drain();
    return;
  }
  $n = intval($m[1]);
  $map = explode(',', (string) DecisionQueueController::GetVariable("Shd142Map"));
  $selfUID = intval(DecisionQueueController::GetVariable("Shd142Self"));
  $drain();
  if (!isset($map[$n]))
    return;
  [$hostUID, $subIdx, $cost] = array_pad(explode(':', $map[$n]), 3, '0');
  $fromMz = SWUFindMzByUID(intval($hostUID));
  $selfMz = SWUFindMzByUID($selfUID);
  if ($fromMz === null || $selfMz === null)
    return;
  $from = GetZoneObject($fromMz);
  if ($from === null || !is_array($from->Subcards ?? null) || !isset($from->Subcards[intval($subIdx)]))
    return;
  $sub = $from->Subcards[intval($subIdx)];
  $scid = is_array($sub) ? ($sub['CardID'] ?? '') : ($sub->CardID ?? '');
  SWUPayInlineAbilityCost(intval($player), intval($cost));   // pay the upgrade's cost
  // "if able" — is Pre Vizsla a valid host for this upgrade? Else defeat it.
  $valid = SWUGetUpgradeValidTargets(intval($player), $scid);
  $canAttach = false;
  foreach ($valid as $vmz) {
    $vo = GetZoneObject($vmz);
    if ($vo !== null && intval($vo->UniqueID ?? 0) === $selfUID) {
      $canAttach = true;
      break;
    }
  }
  if ($canAttach)
    SWUMoveUpgradeCrossUnit($fromMz, intval($subIdx), $selfMz, intval($player));
  else
    SWUDefeatUpgrade(intval($player), $fromMz, intval($subIdx));   // can't attach → defeat instead
};

// ─── SHD_142 Pre Vizsla (When Played / On Attack) — "You may pay the cost of an upgrade attached to
// another non-Vehicle unit. If you do, take control of that upgrade and attach it to this unit, if able.
// If it can't attach to this unit, defeat it instead." Offer the affordable upgrades (staged in TempZone);
// on pick, pay its cost, then move it to Pre Vizsla (new controller) or defeat it if he's not a valid host. ───
function PreVizslaPowerHungryOffer(int $player, string $selfMz): void
{
  global $playerID;
  $playerID = intval($player);
  $self = GetZoneObject($selfMz);
  if (SWUObjGone($self))
    return;
  $selfUID = intval($self->UniqueID ?? 0);
  $ready = SWUTotalPaymentCapacity($player); // Credits/Droids can pay an upgrade's cost (CR 3.13)
  $entries = [];  // [hostUID, subIdx, cardID, cost]
  foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
    foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
      $o = GetZoneObject($mz);
      if (SWUObjGone($o) || !is_array($o->Subcards ?? null))
        continue;
      if (intval($o->UniqueID ?? 0) === $selfUID)
        continue;          // "another" unit
      if (HasTrait($o->CardID ?? '', 'Vehicle'))
        continue;           // "non-Vehicle unit"
      foreach ($o->Subcards as $i => $sub) {
        if (!_SWUUpgradeMatchesMoveFilter($sub, 'nonpilot'))
          continue;
        $scid = is_array($sub) ? ($sub['CardID'] ?? '') : ($sub->CardID ?? '');
        if (strpos(CardType($scid) ?? '', 'Upgrade') === false)
          continue;  // real upgrades only (not tokens)
        if (intval(CardCost($scid)) > $ready)
          continue;            // "you may pay the cost" → affordable only
        $entries[] = [intval($o->UniqueID ?? 0), $i, $scid, intval(CardCost($scid))];
      }
    }
  }
  if (empty($entries))
    return;
  $temp = &GetTempZone($player);
  while (count($temp) > 0)
    array_pop($temp);
  $map = [];
  foreach ($entries as $e) {
    AddTempZone($player, $e[2]);
    $map[] = $e[0] . ':' . $e[1] . ':' . $e[3];
  }
  DecisionQueueController::StoreVariable("Shd142Map", implode(',', $map));
  DecisionQueueController::StoreVariable("Shd142Self", strval($selfUID));
  $tempMZs = [];
  for ($k = 0; $k < count($entries); $k++)
    $tempMZs[] = "myTempZone-$k";
  DecisionQueueController::AddDecision(
    $player,
    "MZMAYCHOOSE",
    implode('&', $tempMZs),
    1,
    tooltip: "Pay_an_upgrade's_cost_to_take_control_of_it_and_attach_it_here?"
  );
  DecisionQueueController::AddDecision($player, "CUSTOM", "SHD_142#move", 1);
}
