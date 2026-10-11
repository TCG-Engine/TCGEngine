<?php
// HMW_236 Booma Ball — Upgrade +2/+2, cost 3, [Cunning], Item/Weapon.
// "When Played: You may return an upgrade that costs 3 or less to its owner's hand."
// HMW_222 Sandcrawler Sales Team's clause without its gate. PRINTED cost, tokens included (they cease
// rather than go to hand). No "another": the Booma Ball itself costs 3 and is a legal pick.

$whenPlayedAbilities["HMW_236:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $targets = SWUGetUpgradeSubcardMzIDs('cost<=3');
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Return_an_upgrade_costing_3_or_less_to_its_owners_hand?",
        "Choose_an_upgrade_to_return", "HMW_236#0");
};

$customDQHandlers["HMW_236#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    SWUDefeatUpgradeByMzID(intval($player), strval($lastDecision), true);
    DecisionQueueController::CleanupRemovedCards();
};
