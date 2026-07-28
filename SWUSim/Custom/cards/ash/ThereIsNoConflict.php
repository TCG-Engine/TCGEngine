<?php
// ASH_199
// Cost 2 - There Is No Conflict - [Cunning,Villainy] - Upgrade Power 2 - Upgrade HP 2
// Text: When Played: Return any number of other upgrades on attached unit to their owners' hands.

// ASH_199 There Is No Conflict (upgrade) — When Played: return any number of OTHER upgrades on the
// attached unit to their owners' hands. Fires via the upgrade WhenPlayed fallback ($mzID = the host unit).
$whenPlayedAbilities["ASH_199:0"] = function($player, $hostMzID) {
    global $playerID; $playerID = intval($player);
    $host = GetZoneObject($hostMzID);
    if (SWUObjGone($host)) return;
    $cands = [];   // CardIDs of OTHER non-token upgrades (exclude ASH_199 itself)
    foreach (GetUpgradesOnUnit($host) as $up) {
        $ucid  = is_array($up) ? ($up['CardID'] ?? '') : ($up->CardID ?? '');
        $isTok = is_array($up) ? !empty($up['IsToken']) : !empty($up->IsToken);
        if ($ucid === '' || $isTok || $ucid === 'ASH_199') continue;
        $cands[] = $ucid;
    }
    if (empty($cands)) return;
    $temp = &GetTempZone(intval($player));
    while (count($temp) > 0) array_pop($temp);
    foreach ($cands as $cid) AddTempZone(intval($player), $cid);
    $tempMZs = [];
    for ($k = 0; $k < count($cands); $k++) $tempMZs[] = "myTempZone-{$k}";
    DecisionQueueController::StoreVariable("ASH199Host", $hostMzID);
    DecisionQueueController::StoreVariable("ASH199Cands", implode(",", $cands));
    DecisionQueueController::AddDecision(intval($player), "MZMULTICHOOSE", "0|" . count($cands) . "|" . implode("&", $tempMZs), 1,
        tooltip: "Return_any_number_of_other_upgrades_to_owners'_hands");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_199#0", 1);
};

$customDQHandlers["ASH_199#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $host     = (string)DecisionQueueController::GetVariable("ASH199Host");
    $candsRaw = (string)DecisionQueueController::GetVariable("ASH199Cands");
    $cands    = $candsRaw === '' ? [] : explode(",", $candsRaw);
    $temp = &GetTempZone(intval($player));
    while (count($temp) > 0) array_pop($temp);
    if (SWUDecisionDeclined($lastDecision)) { DecisionQueueController::CleanupRemovedCards(); return; }
    $picked = [];
    foreach (explode('&', $lastDecision) as $mz) {
        if (preg_match('/-(\d+)$/', trim($mz), $m)) { $n = intval($m[1]); if (isset($cands[$n])) $picked[] = $cands[$n]; }
    }
    foreach ($picked as $cid) SWUReturnUpgradeToHand($host, $cid, intval($player));   // returns one matching copy each
    DecisionQueueController::CleanupRemovedCards();
};
