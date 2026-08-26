<?php
// TWI_187
// Cost 7 - Cad Bane - Hostage Taker - [Cunning,Villainy] - Power 7 - HP 7
// Text: When Played: This unit captures up to 3 enemy non-leader units with a total of 8 or less remaining HP. / On Attack: The defending player may rescue a card they own guarded by this unit. If they do, draw 2 cards.

// TWI_187 Cad Bane — "When Played: This unit captures up to 3 enemy non-leader units with a total of 8
// or less remaining HP. On Attack: The defending player may rescue a card they own guarded by this unit.
// If they do, draw 2 cards." Capture is a budget loop (total remaining HP ≤ 8, ≤ 3 units).
$whenPlayedAbilities["TWI_187:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $captor = GetZoneObject($mzID);
    if (SWUObjGone($captor)) return;
    CadBaneHostageTakerCaptureOffer(intval($player), intval($captor->UniqueID ?? 0), 8, 3);
};

// On Attack: offer the defending player (opponent) a rescue of a captive they own under Cad Bane; if
// taken, Cad Bane's controller draws 2. Queued via an intermediate CUSTOM so the opponent's YESNO runs
// outside the OnAttack $playerID-restore window.
$onAttackAbilities["TWI_187:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $cad = GetZoneObject($mzID);
    if (SWUObjGone($cad) || !is_array($cad->Subcards ?? null)) return;
    $opp = SWUCurrentDefendingSeat(intval($player));  // "the defending player" is DETERMINED by the attack, never OtherPlayer()/GetOpponent()
    $hasCaptive = false;
    foreach ($cad->Subcards as $sub) {
        $isCap = is_array($sub) ? !empty($sub['IsCaptive']) : !empty($sub->IsCaptive);
        $rem   = is_array($sub) ? !empty($sub['removed'])   : !empty($sub->removed);
        $own   = is_array($sub) ? intval($sub['Owner'] ?? 0) : intval($sub->Owner ?? 0);
        if ($isCap && !$rem && $own === $opp) { $hasCaptive = true; break; }
    }
    if (!$hasCaptive) return;
    DecisionQueueController::AddDecision($opp, "YESNO", "-", 1, tooltip: "Rescue_your_captured_card?");
    DecisionQueueController::AddDecision($opp, "CUSTOM", "TWI_187#2|" . intval($cad->UniqueID ?? 0) . "|" . intval($player), 1);
    // Combat owns the after-action.
};

$customDQHandlers["TWI_187#2"] = function($opp, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID;
    $playerID = intval($opp);
    $cadUID     = intval($parts[0] ?? 0);
    $controller = intval($parts[1] ?? 0);
    $cadMz = SWUFindMzByUID($cadUID);
    if ($cadMz === null) return;
    $cad = GetZoneObject($cadMz);
    if ($cad === null || !is_array($cad->Subcards ?? null)) return;
    foreach ($cad->Subcards as $si => $sub) {
        $isCap = is_array($sub) ? !empty($sub['IsCaptive']) : !empty($sub->IsCaptive);
        $rem   = is_array($sub) ? !empty($sub['removed'])   : !empty($sub->removed);
        $own   = is_array($sub) ? intval($sub['Owner'] ?? 0) : intval($sub->Owner ?? 0);
        if ($isCap && !$rem && $own === intval($opp)) {
            array_splice($cad->Subcards, $si, 1);
            DoRescueUnit($sub, $cad);
            if ($controller > 0) DoDrawCard($controller, 2);
            return;
        }
    }
};

$customDQHandlers["TWI_187#3"] = function ($player, $parts, $lastDecision) {
  if (SWUDecisionDeclined($lastDecision))
    return; // declined → stop
  global $playerID;
  $playerID = intval($player);
  $captorUID = intval($parts[0] ?? 0);
  $budget = intval($parts[1] ?? 0);
  $count = intval($parts[2] ?? 0);
  $o = GetZoneObject($lastDecision);
  if (SWUObjGone($o))
    return;
  $rem = intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0);
  $captorMz = SWUFindMzByUID($captorUID);
  if ($captorMz === null)
    return;
  DoCaptureUnit(intval($player), $captorMz, $lastDecision);
  $budget -= $rem;
  $count -= 1;
  if ($budget > 0 && $count > 0)
    CadBaneHostageTakerCaptureOffer(intval($player), $captorUID, $budget, $count);
};

function CadBaneHostageTakerCaptureOffer(int $player, int $captorUID, int $budget, int $count): void
{
  global $playerID;
  $playerID = intval($player);
  if ($count <= 0 || $budget <= 0)
    return;
  $targets = [];
  foreach (["theirGroundArena", "theirSpaceArena"] as $z) {
    foreach (ZoneSearch($z, NonLeaderUnitFilter) as $mz) {
      $o = GetZoneObject($mz);
      if (SWUObjGone($o))
        continue;
      $rem = intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0);
      if ($rem >= 1 && $rem <= $budget)
        $targets[] = $mz;
    }
  }
  if (empty($targets))
    return;
  SWUQueueMayChooseTarget(
    $player,
    $targets,
    "Capture_an_enemy_unit_(remaining_HP_budget_{$budget},_{$count}_left)?",
    "Choose_an_enemy_unit_to_capture",
    "TWI_187#3|{$captorUID}|{$budget}|{$count}"
  );
}
