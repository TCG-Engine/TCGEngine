<?php
// JTL_005
// Cost 5 - Admiral Piett - Commanding the Armada - [Command,Villainy] - Power 2 - HP 7
// Text: Action [Exhaust]: Play a Capital Ship unit from your hand. It costs 1 resource less.
// DeployText: Each Capital Ship unit you play costs 2 resources less.
// Epic Action: If you control 5 or more resources, deploy this leader.

// ── JTL_005 Admiral Piett (leader action continuation) ──────────────────────
// $lastDecision = the chosen Capital Ship. Play it at a 1-resource discount; ActivateCard owns the
// end-of-action.
$customDQHandlers["JTL_005#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) {
        SWUAfterAction(intval($player));
        return;
    }
    global $playerID;
    $playerID = intval($player);
    ActivateCard(intval($player), $lastDecision, false, 1);
};

// JTL_005 Admiral Piett — Leader Action [Exhaust]: Play a Capital Ship unit from your hand. It costs 1
// resource less. Filter the hand playables (at the 1-resource discount) to Capital Ship units; the
// continuation ("JTL_005") plays the chosen card at the discount. (The deployed-side –2 passive lives
// in $playCostFieldModifiers in GameLogic.php and only applies while Piett is in the arena.)
$leaderAbilities["JTL_005"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    $targets = [];
    foreach (SWUHandPlayablesAtDiscount($player, ['Unit'], 1) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && HasTrait($o->CardID, 'Capital Ship')) $targets[] = $mz;
    }
    if (empty($targets)) { SWUAfterAction($player); return; } // no Capital Ship → fizzle
    SWUQueueChooseTarget($player, $targets, "Play_a_Capital_Ship_unit_(costs_1_less)", "JTL_005#0");
};
