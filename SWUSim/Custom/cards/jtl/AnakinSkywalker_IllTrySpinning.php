<?php
// JTL_197
// Cost 2 - Anakin Skywalker - I'll Try Spinning - [Cunning,Heroism] - Power 2 - HP 3 - Upgrade Power 2 - Upgrade HP 3
// Text: / Piloting [2 resources Cunning Heroism] (You may play this as an upgrade on a friendly Vehicle without a Pilot.) / When attached unit completes an attack (and survives): You may return this upgrade to its owner's hand.

// JTL_197 Anakin Skywalker (pilot) — "When attached unit completes an attack (and survives): You may
// return this upgrade to its owner's hand." (The "survives" gate is the surviving-attacker check in
// CollectAfterAttackTriggers; this only fires for a still-living host.)
$onAttackEndFromUpgradeAbilities["JTL_197"] = function($player, $hostMzID) {
    global $playerID;
    $playerID = intval($player);
    $host = GetZoneObject($hostMzID);
    if (SWUObjGone($host)) return;
    $hostUid = intval($host->UniqueID ?? 0);
    DecisionQueueController::AddDecision($player, 'YESNO', '-', 1, tooltip: "Return_Anakin_Skywalker_to_your_hand?");
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'JTL_197#0|' . $hostUid, 1);
};

$customDQHandlers["JTL_197#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID;
    $playerID = intval($player);
    $hostUid = intval($parts[0] ?? 0);
    $hostMz = SWUFindMzByUID($hostUid);
    if ($hostMz === null) return;
    SWUReturnUpgradeToHand($hostMz, 'JTL_197');
};
