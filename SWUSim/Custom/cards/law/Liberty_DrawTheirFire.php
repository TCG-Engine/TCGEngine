<?php
// LAW_224
// Cost 8 - Liberty - Draw Their Fire! - [Cunning,Heroism] - Power 9 - HP 7
// Text: Sentinel / When Played/On Attack: Exhaust an enemy unit and return all upgrades on it that cost 4 or less to their owner's hands.

$customDQHandlers["LAW_224#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    OnExhaustCard(intval($player), $lastDecision);
    $o = GetZoneObject($lastDecision);
    if ($o === null) return;
    // Collect the indices (in GetUpgradesOnUnit order) of real upgrades costing <= 4, then bounce each
    // to its owner's hand — highest index first so the shift from each removal stays valid.
    $idxs = [];
    $ups = GetUpgradesOnUnit($o);
    for ($i = 0; $i < count($ups); $i++) {
        $cid = $ups[$i]->CardID ?? '';
        if ($cid === '' || SWUIsCreditToken($cid)) continue;
        if (stripos(CardType($cid) ?? '', 'Upgrade') === false) continue;   // skip Shield/Exp tokens
        if (intval(CardCost($cid)) <= 4) $idxs[] = $i;
    }
    rsort($idxs);
    foreach ($idxs as $i) SWUDefeatUpgrade(intval($player), $lastDecision, $i, true);   // bounce=true → owner's hand
};

// LAW_224 Liberty — Sentinel + When Played/On Attack: exhaust an enemy unit and return all upgrades on
// it that cost 4 or less to their owners' hands.
$law224 = function ($player, $mzID) {
  global $playerID;
  $playerID = intval($player);
  $enemy = SWUAllUnits('their');
  if (empty($enemy))
    return;
  SWUQueueMayChooseTarget(intval($player), $enemy, "Exhaust_an_enemy_unit_and_return_its_cheap_upgrades?", "Choose_an_enemy_unit", "LAW_224#0");
};

$whenPlayedAbilities["LAW_224:0"] = $law224;

$onAttackAbilities["LAW_224:0"] = $law224;
