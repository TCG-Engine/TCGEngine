<?php
// LAW_017
// Cost 5 - Han Solo - I Got a Really Good Feeling - [Cunning,Heroism] - Power 4 - HP 5
// Text: Action [Exhaust, defeat a friendly token]: Deal 1 damage to a unit.
// DeployText: Saboteur (When this unit attacks, ignore Sentinel and defeat the defender's Shields.) / On Attack: Defeat any number of friendly tokens. Deal damage to a unit equal to the number of tokens defeated this way.
// Epic Action: If you control 5 or more resources, deploy this leader.
//
// Both sides pay in "friendly tokens", which is the SAME cost LAW_019 Alliance Outpost's Epic pays —
// so both reach the pool through SWUFriendlyTokenMzIDs() (CardHelpers.php) and defeat through
// SWUDefeatFriendlyTokenByMzID(). This card used to enumerate the pool itself and whitelisted the token
// kinds it knew (Force / Credit / Experience / Shield / token unit), which refused an ASH_T02 Advantage
// token: the cost read as unpayable, so the gate below switched the whole Action off (game 690588).
// The chosen token is a real mzID, so the prompt is the ordinary card picker — the player clicks the
// token itself.

// FRONT Action [Exhaust, defeat a friendly token]: the token-defeat is a COST (exactly one), then deal 1.
$leaderAbilities["LAW_017"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $tokens = SWUFriendlyTokenMzIDs($player);
    if (empty($tokens)) { SWUAfterAction($player); return; }   // no token to pay the cost → action unusable
    // Not $may: the cost is mandatory once the Action is taken, and a lone token auto-pays.
    SWUQueueChooseTarget($player, $tokens, "Choose_a_friendly_token_to_defeat", "LAW_017#0");
    SWUQueueAfterAction($player);
};

$customDQHandlers["LAW_017#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    SWUDefeatFriendlyTokenByMzID(intval($player), strval($lastDecision));  // pay the cost
    DecisionQueueController::CleanupRemovedCards();
    _SWULaw017DealNToUnit(intval($player), 1);
};

$onAttackAbilities["LAW_017:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SetSWUVar("LAW017_CNT_{$player}", '0');
    HanSoloIGotaReallyGoodFeelingQueueDeployedPick(intval($player));
};

$customDQHandlers["LAW_017#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) { _SWULaw017FinishDeployed(intval($player)); return; }
    SWUDefeatFriendlyTokenByMzID(intval($player), strval($lastDecision));
    SetSWUVar("LAW017_CNT_{$player}", strval(intval(GetSWUVar("LAW017_CNT_{$player}", '0')) + 1));
    DecisionQueueController::CleanupRemovedCards();
    HanSoloIGotaReallyGoodFeelingQueueDeployedPick(intval($player));   // re-offer with the remaining tokens
};

// DEPLOYED On Attack: defeat ANY NUMBER of friendly tokens (0..N); deal that many to a unit. Implemented
// as a pick-one-then-re-offer loop, accumulating the count in a SWUVar; DECLINING the offer is how the
// player stops (and how they choose zero). Recomputing the pool each pass keeps the mzIDs fresh after
// each defeat — a defeated token's mzID would otherwise still be sitting in the next offer.
function HanSoloIGotaReallyGoodFeelingQueueDeployedPick(int $player): void {
    $tokens = SWUFriendlyTokenMzIDs($player);
    if (empty($tokens)) { _SWULaw017FinishDeployed($player); return; }
    // $may: declinable, and it also stops a lone remaining token from auto-paying a cost the player
    // may not want to pay at all.
    SWUQueueChooseTarget($player, $tokens, "Defeat_a_friendly_token_(or_decline)", "LAW_017#1", 1, true);
}
