<?php
// HMW_267 Renew — Event, cost 2, [Heroism], Innate.
// "You may defeat a Condition upgrade. Heal 3 damage from your base."
// Two independent clauses: the heal happens whether or not an upgrade is defeated. A Condition upgrade is
// any upgrade in play with the Condition trait (a Weakness token is one), on any unit or base.

$whenPlayedAbilities["HMW_267:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $targets = array_values(array_filter(SWUGetUpgradeSubcardMzIDs(''), function($mz) {
        $sub = MZParseSubcardID($mz);
        if ($sub === null) return false;
        $host = GetZoneObject($sub['host']);
        $up = $host->Subcards[$sub['subIndex']] ?? null;
        $cid = is_array($up) ? ($up['CardID'] ?? '') : ($up->CardID ?? '');
        return $cid !== '' && HasTrait($cid, 'Condition');
    }));
    if (!empty($targets)) {
        SWUQueueMayChooseTarget(intval($player), $targets, "Defeat_a_Condition_upgrade?", "Choose_a_Condition_upgrade", "HMW_267#0");
    }
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "HMW_267#1", 1);
};

$customDQHandlers["HMW_267#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    SWUDefeatUpgradeByMzID(intval($player), strval($lastDecision), false);
    DecisionQueueController::CleanupRemovedCards();
};

$customDQHandlers["HMW_267#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    OnHealBase(intval($player), intval($player), 3);
};
