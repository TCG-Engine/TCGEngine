<?php
// ASH_210
// Cost 1 - DDC Defender - [Cunning,Heroism] - Upgrade Power 1 - Upgrade HP 0
// Text: Attach to a non-Vehicle unit. / Attached unit gains: "On Defense: You may deal 1 damage to a unit in this unit's arena and exhaust it."

// ASH_210 DDC Defender (upgrade) — host gains "On Defense: you may deal 1 damage to a unit in this unit's
// arena and exhaust it." Fires via the onDefenseFromUpgrade scan (combat-pause is automatic).
$onDefenseFromUpgradeAbilities["ASH_210"] = function($player, $hostMzID) {
    global $playerID; $playerID = intval($player);
    $host = GetZoneObject($hostMzID);
    if (SWUObjGone($host)) return;
    $arena = strpos($hostMzID, 'SpaceArena') !== false ? 'SpaceArena' : 'GroundArena';
    $tg = array_merge(ZoneSearch("my{$arena}", AnyUnitFilter), ZoneSearch("their{$arena}", AnyUnitFilter));
    if (empty($tg)) return;
    SWUQueueMayChooseTarget(intval($player), $tg, "Deal_1_and_exhaust_a_unit_in_this_arena?", "Choose_a_unit", "ASH_210#0");
};

$customDQHandlers["ASH_210#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    SWUDealDamageToUnit($lastDecision, 1, intval($player));
    $still = GetZoneObject($lastDecision);
    if ($still !== null && empty($still->removed)) OnExhaustCard(intval($player), $lastDecision);
};
