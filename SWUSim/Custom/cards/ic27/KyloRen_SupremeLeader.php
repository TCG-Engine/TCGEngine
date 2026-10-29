<?php
// IC27_003
// Cost 5 - Kylo Ren - Supreme Leader - [Command,Villainy] - Leader (deploys as a 5/3 Ground unit)
//   Traits: Force, First Order, Official
// Text:       Action [Exhaust, discard a card from your hand]: Give an Experience token to a unit.
//             Epic Action: If you control 5 or more resources, deploy this leader.
// DeployText: When Deployed: You may return a unit from your discard pile to your hand.
//
// FRONT — SHD_011 Kylo Ren's cost word for word. The discard is a COST: SWULeaderActionAffordable makes the Action
// unavailable with an empty hand, and the pick is mandatory (SWULeaderAction has already exhausted him when this
// runs). The discard goes through DoDiscardCard, so it is a real "discarded from your hand" event (OnCardDiscarded,
// Padmé / Migs reactions, IC27_063's replay stamp). The effect is a mandatory pick over EVERY unit on the table ("a
// unit", SWUAllUnits — enemy units, leader units, a Team Suns teammate's) -> one Experience token. With no unit in
// play the Action is still usable (CR 6.4.587.c — the cost changes the game state) and gives nothing.
// DEPLOYED — When Deployed: an optional pick (MZMAYCHOOSE) over the UNIT cards in his controller's discard ->
// SWUReturnFromDiscardToHand. Epic deploy needs no wiring (threshold = printed cost 5).
// Tests: SWUSim/Tests/Cases/ic27/KyloRen_SupremeLeader.md
$leaderAbilities["IC27_003"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $hand = array_values(ZoneSearch("myHand"));
    if (empty($hand)) { SWUAfterAction($player); return; }   // can't pay the discard cost (also gated up front)
    SWUQueueChooseTarget($player, $hand, "Discard_a_card_from_your_hand_(cost)", "IC27_003#0");
};

$customDQHandlers["IC27_003#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) { SWUAfterAction(intval($player)); return; }
    DoDiscardCard(intval($player), $lastDecision);
    DecisionQueueController::CleanupRemovedCards();
    $targets = array_values(array_filter(SWUAllUnits(), fn($mz) => !SWUObjGone(GetZoneObject($mz))));
    if (empty($targets)) { SWUAfterAction(intval($player)); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Give_an_Experience_token_to_a_unit", "GIVE_EXPERIENCE|1");
    SWUQueueAfterAction(intval($player));
};

$whenPlayedAbilities["IC27_003:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (ZoneSearch('myDiscard', ['Unit']) as $mz) {
        if (!SWUObjGone(GetZoneObject($mz))) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Return_a_unit_from_your_discard_pile_to_your_hand?",
        "Return_a_unit_from_your_discard_pile_to_your_hand", "IC27_003#1");
};

$customDQHandlers["IC27_003#1"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    if (SWUObjGone(GetZoneObject($lastDecision))) return;
    SWUReturnFromDiscardToHand(intval($player), $lastDecision);
};
