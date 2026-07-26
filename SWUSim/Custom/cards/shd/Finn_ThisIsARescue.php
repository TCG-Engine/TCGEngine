<?php
// SHD_003
// Cost 5 - Finn - This is a Rescue - [Heroism,Vigilance] - Power 4 - HP 6
// Text: Action [Exhaust]: Defeat a friendly upgrade on a unit. If you do, give a Shield token to that unit.
// DeployText: On Attack: You may defeat a friendly upgrade on a unit. If you do, give a Shield token to that unit.
// Epic Action: If you control 5 or more resources, deploy this leader.

$leaderAbilities["SHD_003"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $hosts = FinnThisisaRescueUpgradedFriendlies($player);
    if (empty($hosts)) { SWUAfterAction($player); return; }
    DecisionQueueController::StoreVariable("DefeatUpgParams", "1|1|");
    DecisionQueueController::StoreVariable("DefeatUpgThen", "SHD_003#then");
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $hosts), 1, tooltip: "Defeat_a_friendly_upgrade");
    DecisionQueueController::AddDecision($player, "CUSTOM", "DEFEAT_UPGRADE", 1);
    SWUQueueAfterAction($player);
};

$onAttackAbilities["SHD_003:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $hosts = FinnThisisaRescueUpgradedFriendlies(intval($player));
    if (empty($hosts)) return;
    DecisionQueueController::StoreVariable("DefeatUpgParams", "1|1|");
    DecisionQueueController::StoreVariable("DefeatUpgThen", "SHD_003#then");
    DecisionQueueController::AddDecision(intval($player), "MZMAYCHOOSE", implode("&", $hosts), 1, tooltip: "Defeat_a_friendly_upgrade?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "DEFEAT_UPGRADE", 1);
};

// Shared then-handler: shield the host whose upgrade was just defeated ($parts[0] = host mzID).
$customDQHandlers["SHD_003#then"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $hostMz = $parts[0] ?? '';
    if ($hostMz === '' || !str_contains($hostMz, '-')) return;
    $o = GetZoneObject($hostMz);
    if (SWUObjGone($o)) return;
    DoGiveShieldToken(intval($player), $hostMz);
};

// ── SHD_003 Finn ───────────────────────────────────────────────────────────────
// Front Action [Exhaust]: Defeat a friendly upgrade on a unit. If you do, give a Shield token to it.
// Deployed On Attack: same, but "You may".
function FinnThisisaRescueUpgradedFriendlies(int $player): array {
    $hosts = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && count(GetUpgradesOnUnit($o)) > 0) $hosts[] = $mz;
        }
    }
    return $hosts;
}
