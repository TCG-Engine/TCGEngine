<?php
// SEC_165
// Cost 3 - Academy Disciplinarian - [Aggression] - Power 3 - HP 4
// Text: When Played: You may deal 1 damage to a friendly unit with 2 or less power and ready it.

// SEC_165 Academy Disciplinarian — When Played: you may deal 1 to a friendly unit with 2 or less power
// and ready it.
$whenPlayedAbilities["SEC_165:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (SWUAllUnits('my') as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval(ObjectCurrentPower($o)) <= 2) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Deal_1_to_a_friendly_unit_and_ready_it?", "Choose_a_friendly_unit_(power<=2)", "SEC_165#0");
};

$customDQHandlers["SEC_165#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $uid = ($o = GetZoneObject($lastDecision)) !== null ? intval($o->UniqueID ?? 0) : 0;
    SWUDealDamageToUnit($lastDecision, 1, intval($player));
    $mz = SWUFindMzByUID($uid);   // re-resolve (damage may have shifted indices if it died — then no ready)
    if ($mz !== null) OnReadyCard(intval($player), $mz);
};
