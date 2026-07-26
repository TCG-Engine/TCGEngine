<?php
// SEC_149
// Cost 3 - Kaydel Connix - For Our Survival - [Aggression,Heroism] - Power 3 - HP 2
// Text: When Played: You may defeat all non-<uq> (non-unique) upgrades on a unit. / Plot (When you deploy a leader, you may play this card from your resources, paying its cost. Replace it with the top card of your deck.)

// SEC_149 Kaydel Connix — When Played: you may defeat ALL non-unique upgrades on a unit. (Plot auto.)
// Forced-all once a unit is chosen (no per-upgrade sub-selection).
$sec149NonUniqueIdxs = function ($host): array {
  $idxs = [];
  foreach (GetUpgradesOnUnit($host) as $i => $up) {
    $cid = is_array($up) ? ($up['CardID'] ?? '') : ($up->CardID ?? '');
    $isCap = is_array($up) ? !empty($up['IsCaptive']) : !empty($up->IsCaptive);
    if ($isCap || $cid === '')
      continue;
    if (CardUnique($cid))
      continue;
    if (strpos(strtolower(CardType($cid) ?? ''), 'token') !== false)
      continue;   // tokens aren't "upgrades" here
    $idxs[] = $i;
  }
  return $idxs;
};

$whenPlayedAbilities["SEC_149:0"] = function ($player, $mzID) use ($sec149NonUniqueIdxs) {
  global $playerID;
  $playerID = intval($player);
  $hosts = [];
  foreach (SWUAllUnits() as $mz) {
    $o = GetZoneObject($mz);
    if ($o !== null && empty($o->removed) && !empty($sec149NonUniqueIdxs($o)))
      $hosts[] = $mz;
  }
  if (empty($hosts))
    return;
  SWUQueueMayChooseTarget(intval($player), $hosts, "Defeat_all_non-unique_upgrades_on_a_unit?", "Choose_a_unit", "SEC_149#0");
};

$customDQHandlers["SEC_149#0"] = function ($player, $parts, $lastDecision) use ($sec149NonUniqueIdxs) {
  if (SWUDecisionDeclined($lastDecision))
    return;
  global $playerID;
  $playerID = intval($player);
  $host = GetZoneObject($lastDecision);
  if (SWUObjGone($host))
    return;
  $idxs = $sec149NonUniqueIdxs($host);
  rsort($idxs);   // descending → index-shift safe
  foreach ($idxs as $i)
    SWUDefeatUpgrade(intval($player), $lastDecision, $i);
};
