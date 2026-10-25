<?php
// ASH_171
// Cost 3 - Pegasus Tri-Wing - [Aggression] - Power 3 - HP 2
// Text: When Played: You may defeat a friendly upgrade. If you do, ready this unit.

// ASH_171 Pegasus Tri-Wing — When Played: you may defeat a FRIENDLY upgrade; if you do, ready this unit.
// Friendly-scoped host pick (the universal defeat flow spans both sides); the DefeatUpgThen continuation
// readies the Pegasus by its UID (the chain only fires when an upgrade was actually defeated).
$whenPlayedAbilities["ASH_171:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self    = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self, 0);
    $hosts = SWUFriendlyUpgradeHosts(intval($player));   // CR: friendly = I control it (tokens: I control its host)
    if (empty($hosts)) return;
    DecisionQueueController::StoreVariable("DefeatUpgParams", "1|1|");
    DecisionQueueController::StoreVariable("DefeatUpgThen", "ASH_171#0");
    SWUFriendlyUpgradeOnlyOnHost(intval($player));
    DecisionQueueController::StoreVariable("ASH171SelfUID", strval($selfUID));
    DecisionQueueController::AddDecision(intval($player), "MZMAYCHOOSE", implode("&", $hosts), 1, tooltip: "Defeat_a_friendly_upgrade_to_ready_this_unit?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "DEFEAT_UPGRADE", 1);
};

$customDQHandlers["ASH_171#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $uid = intval(DecisionQueueController::GetVariable("ASH171SelfUID"));
    DecisionQueueController::StoreVariable("ASH171SelfUID", "");
    if ($uid <= 0) return;
    $mz = SWUFindMzByUID($uid);
    if ($mz === null) return;
    OnReadyCard(intval($player), $mz);   // ready this unit
};
