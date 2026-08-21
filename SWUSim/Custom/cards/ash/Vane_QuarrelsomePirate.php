<?php
// ASH_012
// Cost 5 - Vane - Quarrelsome Pirate - [Aggression,Villainy] - Power 3 - HP 6
// Text: Action [Exhaust, defeat a friendly upgrade]: Deal 2 damage to a base.
// DeployText: On Attack: You may defeat a friendly upgrade. If you do, deal 2 damage to the defending unit or a base.
// Epic Action: If you control 5 or more resources, deploy this leader.

// ASH_012 Vane — may defeat a friendly upgrade; if you do, deal 2 to the defending unit or a base.
$onAttackAbilities["ASH_012:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $hosts = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && count(GetUpgradesOnUnit($o)) > 0) $hosts[] = $mz;
        }
    }
    if (empty($hosts)) return;   // can't defeat an upgrade → "if you do" never triggers
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1,
        tooltip: "Defeat_a_friendly_upgrade_to_deal_2_damage?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_012#1", 1);
};

$customDQHandlers["ASH_012#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (($lastDecision ?? '') !== 'YES') return;
    $hosts = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && count(GetUpgradesOnUnit($o)) > 0) $hosts[] = $mz;
        }
    }
    if (empty($hosts)) return;
    DecisionQueueController::StoreVariable("DefeatUpgParams", "1|1|");
    DecisionQueueController::StoreVariable("DefeatUpgThen", "ASH_012#2");
    if (count($hosts) === 1) DecisionQueueController::AddDecision(intval($player), "PASSPARAMETER", $hosts[0], 1);
    else DecisionQueueController::AddDecision(intval($player), "MZCHOOSE", implode("&", $hosts), 1, "Defeat_a_friendly_upgrade");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "DEFEAT_UPGRADE", 1);
};

$customDQHandlers["ASH_012#2"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $targets = SWUAllBaseMzIDs(intval($player), 'any');
    $def = GetSWUVar('SWU_CURRENT_DEFENDER');
    if ($def && strpos($def, 'Arena') !== false) {
        $d = GetZoneObject($def);
        if ($d !== null && empty($d->removed)) $targets[] = $def;
    }
    SWUQueueChooseTarget(intval($player), $targets, "Deal_2_to_the_defending_unit_or_a_base", "DEAL_TARGET|2");
};

// ASH_012 Vane — Action [Exhaust, defeat a friendly upgrade]: deal 2 damage to a base. The upgrade defeat
// is a COST (friendly-scoped, mandatory); the DefeatUpgThen continuation deals the 2 to a chosen base.
$leaderAbilities["ASH_012"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $hosts = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && count(GetUpgradesOnUnit($o)) > 0) $hosts[] = $mz;
        }
    }
    if (empty($hosts)) { SWUAfterAction($player); return; }   // no friendly upgrade → can't pay the cost
    DecisionQueueController::StoreVariable("DefeatUpgParams", "1|1|");
    DecisionQueueController::StoreVariable("DefeatUpgThen", "ASH_012#0");
    if (count($hosts) === 1) DecisionQueueController::AddDecision($player, "PASSPARAMETER", $hosts[0], 1);
    else DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $hosts), 1, "Defeat_a_friendly_upgrade_(cost)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "DEFEAT_UPGRADE", 1);
};

$customDQHandlers["ASH_012#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    SWUOfferBaseTarget(intval($player), ['continuation'=>'DEAL_BASE_DAMAGE','amount'=>2,'prompt'=>"Deal_2_damage_to_a_base"]);
    SWUQueueAfterAction(intval($player));
};
