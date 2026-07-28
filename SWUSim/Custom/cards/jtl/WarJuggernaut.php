<?php
// JTL_170
// Cost 6 - War Juggernaut - [Aggression] - Power 3 - HP 7
// Text: This unit gets +1/+0 for each damaged unit. / When Played: Deal 1 damage to each of any number of units.

// ── JTL_170 War Juggernaut — When Played: Deal 1 damage to each of ANY NUMBER of units. (The +1/+0
// per damaged unit passive lives in ObjectCurrentPower.) ─────────────────────────────────────────────
$whenPlayedAbilities["JTL_170:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = SWUAllUnits();
    if (empty($targets)) return;
    $max = count($targets); // "any number" — effectiveMax = candidate count so Select All shows
    DecisionQueueController::AddDecision($player, "MZMULTICHOOSE",
        "0|" . $max . "|" . implode("&", $targets), 1, tooltip: "Deal_1_damage_to_any_number_of_units");
    DecisionQueueController::AddDecision($player, "CUSTOM", "JTL_170#0", 1);
};

$customDQHandlers["JTL_170#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision) || $lastDecision === '') return;
    global $playerID;
    $playerID = intval($player);
    $uids = [];
    foreach (explode("&", $lastDecision) as $mz) {
        if ($mz === '' || $mz === '-') continue;
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID ?? 0);
    }
    foreach ($uids as $uid) {
        $mz = SWUFindMzByUID($uid);
        if ($mz !== null && $mz !== '') SWUDealDamageToUnit($mz, 1, intval($player));
    }
};
