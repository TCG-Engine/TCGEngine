<?php
// JTL_232
// Cost 2 - Jump to Lightspeed - [Cunning]
// Text: Return a friendly space unit and any number of non-leader upgrades on it to their owners' hands. The next time you play a copy of that unit this phase, you may play it for free.

// ── JTL_232 Jump to Lightspeed — return the chosen space unit and ANY NUMBER of its non-leader upgrades
// to owners' hands; then arm the one-shot "next copy of that unit is free this phase" rider. #0 picks the
// unit; if it carries returnable upgrades, stage them and let the player choose which to return (#1). ────
$jtl232_realUpgradeCardID = function ($sub): array {
  // → ['ok'=>bool, 'cid'=>string, 'owner'=>int] for a returnable (non-leader/non-token/non-captive) upgrade
  $scid = is_array($sub) ? ($sub['CardID'] ?? '') : ($sub->CardID ?? '');
  $sown = is_array($sub) ? intval($sub['Owner'] ?? 0) : intval($sub->Owner ?? 0);
  $isCap = is_array($sub) ? !empty($sub['IsCaptive']) : !empty($sub->IsCaptive);
  $type = strtolower(CardType($scid) ?? '');
  $ok = ($scid !== '' && !$isCap && strpos($type, 'leader') === false && strpos($type, 'token') === false);
  return ['ok' => $ok, 'cid' => $scid, 'owner' => $sown];
};

$customDQHandlers["JTL_232#0"] = function ($player, $parts, $lastDecision) use ($jtl232_realUpgradeCardID) {
  if (SWUDecisionDeclined($lastDecision) || $lastDecision === '')
    return;
  global $playerID;
  $playerID = intval($player);
  $unitMz = $lastDecision;
  $o = GetZoneObject($unitMz);
  if (SWUObjGone($o))
    return;
  $returnedCardID = $o->CardID ?? '';
  // Collect the unit's returnable upgrades (in Subcards order) — these are the "any number" the player
  // may return to hand. Tokens are never returnable (removed by the bounce), captives/leaders excluded.
  $realCids = [];
  if (!empty($o->Subcards) && is_array($o->Subcards)) {
    foreach ($o->Subcards as $sub) {
      $r = $jtl232_realUpgradeCardID($sub);
      if ($r['ok'])
        $realCids[] = $r['cid'];
    }
  }
  // No returnable upgrades → bounce the unit now (tokens are removed by the bounce) and arm the rider.
  if (empty($realCids)) {
    SWUBounceUnit(intval($player), $unitMz);
    if ($returnedCardID !== '')
      AddGlobalEffects(intval($player), 'SWU_JTL232_FREE|' . $returnedCardID);
    return;
  }
  // Stage returnable upgrades into TempZone (myTempZone-k ↔ the k-th returnable upgrade) and offer an
  // "any number" pick. Answer '-' to return NONE (they are then defeated when the unit bounces).
  $temp = &GetTempZone($player);
  while (count($temp) > 0)
    array_pop($temp);
  foreach ($realCids as $cid)
    AddTempZone($player, $cid);
  $tempMZs = [];
  for ($k = 0; $k < count($realCids); $k++)
    $tempMZs[] = "myTempZone-" . $k;
  DecisionQueueController::AddDecision(
    $player,
    "MZMULTICHOOSE",
    "0|" . count($realCids) . "|" . implode("&", $tempMZs),
    1,
    tooltip: "Return_any_number_of_upgrades_to_hand"
  );
  DecisionQueueController::AddDecision($player, "CUSTOM", "JTL_232#1|" . $unitMz . "|" . $returnedCardID, 1);
};

// #1 — $lastDecision = the chosen myTempZone-N picks (&-delimited; '-'/'' = none). Return the selected
// upgrades to their owners' hands; leave the rest on the unit so the bounce defeats them (CR 9.3).
$customDQHandlers["JTL_232#1"] = function ($player, $parts, $lastDecision) use ($jtl232_realUpgradeCardID) {
  global $playerID;
  $playerID = intval($player);
  $unitMz = $parts[0] ?? '';
  $returnedCardID = $parts[1] ?? '';
  $selected = [];
  if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS' && $lastDecision !== '') {
    foreach (explode('&', $lastDecision) as $mz) {
      $mz = trim($mz);
      if (preg_match('/-(\d+)$/', $mz, $m))
        $selected[intval($m[1])] = true;
    }
  }
  $temp = &GetTempZone($player);
  while (count($temp) > 0)
    array_pop($temp); // drain the staging zone
  $o = &GetZoneObject($unitMz);
  if ($o !== null && empty($o->removed) && !empty($o->Subcards) && is_array($o->Subcards)) {
    $keep = [];
    $k = 0; // $k counts returnable upgrades in Subcards order — matches the #0 staging
    foreach ($o->Subcards as $sub) {
      $r = $jtl232_realUpgradeCardID($sub);
      if ($r['ok']) {
        if (isset($selected[$k]))
          AddHand($r['owner'] <= 0 ? $player : $r['owner'], CardID: $r['cid']); // returned
        else
          $keep[] = $sub; // not chosen → stays on the unit, defeated on bounce
        $k++;
      } else {
        $keep[] = $sub; // tokens/leaders/captives — handled by the bounce
      }
    }
    $o->Subcards = $keep;
  }
  SWUBounceUnit(intval($player), $unitMz);
  // "The next time you play a copy of that unit this phase, you may play it for free." One-shot, keyed on
  // the exact CardID (a different card sharing only the title does NOT qualify). Honored in
  // SWUComputePlayCost (forces cost 0), consumed in ActivateCard, cleared at RegroupPhaseStart.
  if ($returnedCardID !== '')
    AddGlobalEffects(intval($player), 'SWU_JTL232_FREE|' . $returnedCardID);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_232:0"] = function($player, $mzID = '') {
// Jump to Lightspeed — return a friendly space unit (and its non-leader upgrades)
                          // to owners' hands (continuation JTL_232; free-replay rider deferred).
            global $playerID;
            $playerID = intval($player);
            $space = ZoneSearch("mySpaceArena", AnyUnitFilter);
            // Exclude only a deployed LEADER UNIT (own CardID is a leader) — a vehicle carrying a leader
            // PILOT is a valid target: it returns to hand and its leader pilot is defeated to the leader
            // zone (SWUBounceUnit handles the pilot). Don't use IsLeaderUnit here (it's true for both).
            $space = array_values(array_filter($space, function($mz) {
                $o = GetZoneObject($mz);
                return $o !== null && strpos(CardType($o->CardID ?? '') ?? '', 'Leader') === false;
            }));
            if (empty($space)) return;
            SWUQueueChooseTarget(intval($player), $space, "Return_a_friendly_space_unit_to_hand", "JTL_232#0");
            return;
};
