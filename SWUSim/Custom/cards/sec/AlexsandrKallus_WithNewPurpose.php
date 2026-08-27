<?php
// SEC_155
// Cost 7 - Alexsandr Kallus - With New Purpose - [Aggression,Heroism] - Power 6 - HP 6
// Text: While you have the initiative, each other friendly <uq> (unique) unit gains Raid 2. / When Played: Deal 2 damage to each of up to 3 ground units.

// SEC_155 Alexsandr Kallus — (Raid passive in KeywordEffects) + When Played: deal 2 to each of up to 3 ground units.
$whenPlayedAbilities["SEC_155:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $ground = SWUAllUnits(null, GroundArena);
    if (empty($ground)) return;
    DecisionQueueController::AddDecision(intval($player), "MZMULTICHOOSE", "0|3|" . implode('&', $ground), 1, tooltip: "Deal_2_to_each_of_up_to_3_ground_units");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SEC_155#0", 1, dontSkipOnPass: 1);
};

$customDQHandlers["SEC_155#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $picks = array_slice(array_values(array_filter(explode('&', (string)$lastDecision), fn($m) => $m !== '' && $m !== '-')), 0, 3);
    // snapshot UIDs first (deals are simultaneous; index shifts as units die)
    $uids = [];
    foreach ($picks as $mz) { $o = GetZoneObject($mz); if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID ?? 0); }
    foreach ($uids as $uid) { $mz = SWUFindMzByUID($uid); if ($mz !== null) SWUDealDamageToUnit($mz, 2, intval($player)); }
};
