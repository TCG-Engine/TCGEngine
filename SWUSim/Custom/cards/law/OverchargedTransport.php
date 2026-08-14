<?php
// LAW_195
// Cost 4 - Overcharged Transport - [Aggression] - Power 4 - HP 3
// Text: When Played/When Defeated: You may defeat an upgrade attached to a space unit.

$customDQHandlers["LAW_195#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    SWUQueueDefeatUpgrade(intval($player), "Defeat_an_upgrade_on_that_space_unit", may: false, max: 1, min: 1, onlyHostUID: intval($o->UniqueID ?? 0));
};

$law195 = function ($player, $mzID) {
  global $playerID;
  $playerID = intval($player);
  $hosts = OverchargedTransportSpaceHosts(intval($player));
  if (empty($hosts))
    return;
  if (count($hosts) === 1) {
    SWUQueueDefeatUpgrade(intval($player), "Defeat_an_upgrade_on_that_space_unit?", may: true, max: 1, min: 0, onlyHostUID: $hosts[0]['uid']);
    return;
  }
  $mzs = array_map(fn($h) => $h['mz'], $hosts);
  SWUQueueMayChooseTarget(intval($player), $mzs, "Defeat_an_upgrade_on_a_space_unit?", "Choose_a_space_unit", "LAW_195#0");
};

$whenPlayedAbilities["LAW_195:0"] = $law195;

$whenDefeatedAbilities["LAW_195:0"] = $law195;

// LAW_195 Overcharged Transport — When Played/When Defeated: you may defeat an upgrade attached to a
// space unit. Collect space-arena hosts bearing a real upgrade; scope the upgrade-defeat to one host.
function OverchargedTransportSpaceHosts(int $player): array
{
  global $playerID;
  $playerID = $player;
  $hosts = [];
  foreach (["mySpaceArena", "theirSpaceArena"] as $z) {
    foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
      $o = GetZoneObject($mz);
      if (SWUObjGone($o))
        continue;
      foreach (GetUpgradesOnUnit($o) as $u) {
        $cid = $u->CardID ?? '';
        // An attached PILOT is an upgrade too — its printed CardType is "Unit", so the printed-type
        // gate alone misses pilot-only hosts (the pilot-as-upgrade dispatch-path family).
        $isUpgrade = stripos(CardType($cid) ?? '', 'Upgrade') !== false
                  || !empty($u->IsPilot) || ($cid !== '' && HasTrait($cid, 'Pilot'));
        if ($cid !== '' && !SWUIsCreditToken($cid) && $isUpgrade) {
          $hosts[] = ['mz' => $mz, 'uid' => intval($o->UniqueID ?? 0)];
          break;
        }
      }
    }
  }
  return $hosts;
}
