<?php
// SOR_003
// Cost 7 - Chewbacca - Walking Carpet - [Vigilance,Heroism] - Power 2 - HP 9
// Text: Action [exhaust]: Play a unit that costs 3 or less from your hand (paying its cost). It gains Sentinel for this phase.
// DeployText: Sentinel (Units in this arena can't attack your non-Sentinel units or your base.) / Grit (This unit gains +1/+0 for each damage on it.)
// Epic Action: If you control 7 or more resources, deploy this leader.

// SOR_003 Chewbacca — plays the chosen ≤3 unit ($lastDecision) at full cost, granting it Sentinel
// for this phase via the SOR_003 turn-effect token (applied to the entering unit by ActivateCard's
// $gPlayGrantTurnEffect hook). ActivateCard owns the play's end-of-action — do not add SWU_AFTER_ACTION.
$customDQHandlers["SOR_003#0"] = function($player, $parts, $lastDecision) {
    global $playerID, $gPlayGrantTurnEffect;
    if (SWUDecisionDeclined($lastDecision)) {
        SWUAfterAction($player);
        return;
    }
    $playerID = intval($player);
    $gPlayGrantTurnEffect = 'SOR_003'; // Sentinel for this phase (registry-driven)
    ActivateCard(intval($player), $lastDecision, false, 0);
    $gPlayGrantTurnEffect = null;
};

// SOR_003 Chewbacca — Leader Action [Exhaust]: Play a unit that costs 3 or less from your hand
// (paying its cost). It gains Sentinel for this phase. The leader exhaust is handled by
// SWULeaderAction; this closure offers the ≤3 affordable hand units. The play + Sentinel grant
// happen in SOR_003 (it owns the end-of-action via ActivateCard, so no SWUAfterAction here on
// the play path — only on the empty-target fizzle).
$leaderAbilities["SOR_003"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    $targets = [];
    foreach (SWUHandPlayablesAtDiscount($player, ['Unit'], 0) as $mz) {
        $o = GetZoneObject($mz);
        // "costs 3 or less" gates on PRINTED cost (COST is always printed — the cost-semantics rule);
        // affordability of the EFFECTIVE cost (aspect penalty included, "paying its cost") is already
        // enforced by SWUHandPlayablesAtDiscount. An off-aspect printed-3 unit is offered and pays 5.
        if ($o !== null && empty($o->removed) && intval(CardCost($o->CardID ?? '')) <= 3) $targets[] = $mz;
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets,
        'Play_a_unit_costing_3_or_less_(it_gains_Sentinel)', 'SOR_003#0');
};
