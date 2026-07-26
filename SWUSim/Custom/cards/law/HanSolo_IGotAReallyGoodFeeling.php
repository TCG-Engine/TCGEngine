<?php
// LAW_017
// Cost 5 - Han Solo - I Got a Really Good Feeling - [Cunning,Heroism] - Power 4 - HP 5
// Text: Action [Exhaust, defeat a friendly token]: Deal 1 damage to a unit.
// DeployText: Saboteur (When this unit attacks, ignore Sentinel and defeat the defender's Shields.) / On Attack: Defeat any number of friendly tokens. Deal damage to a unit equal to the number of tokens defeated this way.
// Epic Action: If you control 5 or more resources, deploy this leader.

// FRONT Action [Exhaust, defeat a friendly token]: the token-defeat is a COST (exactly one), then deal 1.
$leaderAbilities["LAW_017"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $opts = _SWULaw017TokenOptions($player);
    if (empty($opts)) { SWUAfterAction($player); return; }   // no token to pay the cost → action unusable
    DecisionQueueController::AddDecision($player, "OPTIONCHOOSE", implode('&', $opts), 1, tooltip: "Choose_a_friendly_token_to_defeat");
    DecisionQueueController::AddDecision($player, "CUSTOM", "LAW_017#0", 1);
    SWUQueueAfterAction($player);
};

$customDQHandlers["LAW_017#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    if (!HanSoloIGotaReallyGoodFeelingDefeatOption(intval($player), $lastDecision)) return;
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
    if (!$lastDecision || $lastDecision === 'Done' || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') {
        _SWULaw017FinishDeployed(intval($player)); return;
    }
    if (HanSoloIGotaReallyGoodFeelingDefeatOption(intval($player), $lastDecision)) {
        SetSWUVar("LAW017_CNT_{$player}", strval(intval(GetSWUVar("LAW017_CNT_{$player}", '0')) + 1));
        DecisionQueueController::CleanupRemovedCards();
    }
    HanSoloIGotaReallyGoodFeelingQueueDeployedPick(intval($player));   // re-offer with the remaining tokens
};

// Defeat the token identified by one option string. Returns true if one was defeated. Callers recompute
// the option list after each defeat, so an mzID inside an option is always freshly resolved.
function HanSoloIGotaReallyGoodFeelingDefeatOption(int $player, string $opt): bool {
    global $playerID; $playerID = $player;
    if ($opt === 'Force') {
        if (!PlayerHasTheForce($player)) return false;
        RemoveGlobalEffect($player, 'SWU_HAS_FORCE'); // the Force token is defeated (set aside), not "used"
        return true;
    }
    if (strncmp($opt, 'Credit', 6) === 0) {            // any usable credit (all interchangeable)
        $cr = SWUUsableCreditTokenMzIDs($player);
        if (empty($cr)) return false;
        SWUDefeatCreditToken($cr[0]);
        return true;
    }
    $p = explode('~', $opt);
    if ($p[0] === 'Exp'    && isset($p[1])) return SWUDefeatExperienceToken($p[1]);
    if ($p[0] === 'Shield' && isset($p[1])) return _SWUDefeatNamedUpgrade(GetZoneObject($p[1]), 'SOR_T02');
    if ($p[0] === 'Unit'   && isset($p[1])) {
        $o = GetZoneObject($p[1]);
        if ($o !== null && empty($o->removed)) { SWUDefeatUnit($player, $p[1]); return true; }
    }
    return false;
}

// DEPLOYED On Attack: defeat ANY NUMBER of friendly tokens (0..N); deal that many to a unit. Implemented
// as a pick-one-then-re-offer loop (with a Done option), accumulating the count in a SWUVar. Recomputing
// the options each pass keeps mzIDs fresh after each defeat.
function HanSoloIGotaReallyGoodFeelingQueueDeployedPick(int $player): void {
    $opts = _SWULaw017TokenOptions($player);
    if (empty($opts)) { _SWULaw017FinishDeployed($player); return; }
    $opts[] = 'Done';
    DecisionQueueController::AddDecision($player, "OPTIONCHOOSE", implode('&', $opts), 1, tooltip: "Defeat_a_friendly_token_(or_Done)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "LAW_017#1", 1);
}
