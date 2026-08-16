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
// USER RULING (2026-08-15): an upgrade is OWNED AND CONTROLLED BY THE PLAYER WHO PLAYED IT, and it may
// sit on ANY eligible unit — yours OR an opponent's. So "a FRIENDLY upgrade" means "an upgrade YOU
// control", wherever it is attached, NOT "any upgrade on a unit you control".
// Scanning only your own arenas got this wrong in both directions: your own upgrade sitting on an ENEMY
// unit was unreachable (the ability fizzled entirely), while an ENEMY's upgrade on your unit was offered.
function FinnThisisaRescueUpgradedFriendlies(int $player): array {
    $hosts = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o === null || !empty($o->removed)) continue;
            foreach (GetUpgradesOnUnit($o) as $sub) {
                $ctrl = is_array($sub) ? intval($sub['Controller'] ?? 0) : intval($sub->Controller ?? 0);
                if ($ctrl === intval($player)) { $hosts[] = $mz; break; }   // this host carries one of MINE
            }
        }
    }
    return $hosts;
}
