<?php
// LAW_003
// Cost 5 - Agent Kallus - Reconsider Your Allegiance - [Vigilance,Villainy] - Power 3 - HP 6
// Text: Action [1 resource, Exhaust]: Play a card from your hand, ignoring its aspect penalties.
// DeployText: Action [1 resource]: Play a card from your hand, ignoring its aspect penalties. / When you play a Heroism card: Heal 2 damage from your base.
// Epic Action: If you control 5 or more resources, deploy this leader.

// LAW_003 Agent Kallus (deployed) — "Action [1 resource]: Play a card from your hand, ignoring its
// aspect penalties." No exhaust (repeatable while resources last); the framework pays the 1 resource.
$unitActionCostKind["LAW_003"] = 'none';

$unitActionResourceCosts["LAW_003"] = 1;

$unitAbilities["LAW_003"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    AgentKallusReconsiderYourAllegianceOfferPlay(intval($player));   // shared with the front action (resource already paid by the framework)
};

$leaderAbilities["LAW_003"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUExhaustResources($player, SWUApplyCostHalving($player, 1))) { SWUAfterAction($player); return; }
    AgentKallusReconsiderYourAllegianceOfferPlay($player);
};

$leaderActionResourceCosts["LAW_003"] = 1;

$customDQHandlers["LAW_003#0"] = function($player, $parts, $lastDecision) {
    global $playerID, $gTurnPlayer; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) { SWUAfterAction(intval($player)); return; }
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) { SWUAfterAction(intval($player)); return; }
    $discount = SWUAspectPenalty(intval($player), $o->CardID);   // waive the FULL aspect penalty
    $savedTP = $gTurnPlayer; $savedPass = GetSWUVar('PASS', '0');
    ActivateCard(intval($player), $lastDecision, false, $discount);
    $gTurnPlayer = $savedTP; SetSWUVar('PASS', $savedPass);
    SWUAfterAction(intval($player));
};

// ── LAW_003 Agent Kallus ──────────────────────────────────────────────────────
// Front Action [1 resource, Exhaust] AND deployed Action [1 resource]: play a card from hand ignoring
// its aspect penalties. Deployed also: "When you play a Heroism card: heal 2 from your base" (in
// SWUCollectOwnPlayReactions). The deployed unit Action is registered in CardDQHandlers (after the
// $unitAbilities reset).
function AgentKallusReconsiderYourAllegianceOfferPlay(int $player): void {
    global $playerID; $playerID = $player;
    $ready = SWUResourceCount($player, readyOnly: true);
    $hand  = GetHand($player);
    $targets = [];
    for ($i = 0; $i < count($hand); $i++) {
        $c = $hand[$i];
        if (SWUObjGone($c)) continue;
        $cid = $c->CardID;
        if (_SWUCantPlayFromHand($cid)) continue;
        $eff = max(0, SWUComputePlayCost($player, $c) - SWUAspectPenalty($player, $cid));
        if ($ready >= $eff) $targets[] = "myHand-{$i}";
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Play_a_card_ignoring_its_aspect_penalties", "LAW_003#0");
}
