<?php
// SEC_200
// Cost 2 - Junior Senator - [Cunning,Heroism] - Power 3 - HP 2
// Text: When Played: You may return an upgrade that costs 3 or less to its owner's hand.

// SEC_200 Junior Senator — When Played: you may return an upgrade that costs 3 or less to its owner's hand.
// The player chooses the UPGRADE itself, not a unit: every eligible upgrade is offered as a subcard mzID
// ("<host>.u<sub>", the HMW_236 Booma Ball / HMW_222 Sandcrawler Sales Team convention). A host-first pick
// that returned "the first eligible upgrade" left the player no way to choose between two cheap upgrades on
// one unit (e.g. a Weakness token beside a 2-cost upgrade).
// SWUGetUpgradeSubcardMzIDs: both sides and every live seat (ZoneSearch fan-out), bases too (Fortify
// upgrades), TOKEN upgrades included, captives excluded. 'cost<=3' reads the PRINTED cost (official ruling).
$whenPlayedAbilities["SEC_200:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = SWUGetUpgradeSubcardMzIDs('cost<=3');
    if (empty($targets)) return;
    // The tooltip says "return … hand" so the bot reads the pick as hostile (_SWUBotTooltipEffect) and its
    // 'upgradepicks' scoring decides which upgrade — an enemy upgrade, or a Weakness on its own unit.
    SWUQueueMayChooseTarget(intval($player), $targets, "Return_an_upgrade_(cost_3_or_less)_to_its_owner's_hand?",
        "Choose_an_upgrade_(cost_3_or_less)_to_return_to_its_owner's_hand", "SEC_200#0");
};

$customDQHandlers["SEC_200#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $sub = MZParseSubcardID(strval($lastDecision));
    if ($sub === null) return;
    $up = MZResolveObject(strval($lastDecision));
    if (!is_object($up) || !empty($up->IsCaptive)) return;
    $ucid = strval($up->CardID ?? '');
    if ($ucid === '' || intval(CardCost($ucid)) > 3) return;
    // ONE path for real and token upgrades alike: SWUReturnUpgradeToHand already makes a token cease to exist
    // instead of reaching a hand (CR), applies SEC_061 Willrow Hood's protection, and writes the game-log line.
    // A bespoke token splice here once removed the token SILENTLY (bug report P-10). The subcard key pins the
    // exact copy, so two same-CardID upgrades with different owners return the one that was chosen.
    SWUReturnUpgradeToHand($sub['host'], $ucid, intval($player), $sub['subIndex']);
};
