<?php
// HMW_010
// Cost 6 - Tarfful - Fighting from the Shadowlands - [Command,Heroism] - Leader (Ground) 3/7
// Traits: Rebel, Wookiee
// Text: Action [2 resources, Exhaust, discard a card from your hand]: Create a Beast token.
//       Epic Action: If you control 6 or more resources, deploy this leader.
// DeployText: Sentinel / On Attack: You may pay [1 resource]. If you do, create a Beast token.
//
// Sentinel needs no code: it is printed on the deployed side, so the generator registers HMW_010 in
// $Sentinel_Cards and the deployed leader unit reads it like any other innate keyword.
// The 2-resource component of the front Action lives in $leaderActionResourceCosts (LeaderAbilities.php);
// the "discard a card from your hand" component is an ADDITIONAL cost and is gated in
// SWULeaderActionAffordable, so an empty hand leaves the action unavailable and the leader READY.

// ── FRONT: Action [2 resources, Exhaust, discard a card from your hand]: create a Beast ────────────
// By the time this runs the resource + exhaust components are already paid. What remains is the
// discard: a COST the player chooses from their whole hand (mandatory once the action is taken, so a
// plain choose rather than a may-choose). The token is created in the continuation, after the discard
// has actually happened — "create" must not fire if the cost somehow fails.
$leaderAbilities["HMW_010"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $hand = array_values(ZoneSearch("myHand"));
    if (empty($hand)) { SWUAfterAction($player); return; }   // belt-and-braces; the gate already refused
    SWUQueueChooseTarget($player, $hand, "Discard_a_card_from_your_hand_(cost)", "HMW_010#0");
    SWUQueueAfterAction($player);
};

$customDQHandlers["HMW_010#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    DoDiscardCard(intval($player), $lastDecision);
    DecisionQueueController::CleanupRemovedCards();
    SWUCreateUnitToken(intval($player), 'HMW_T03');   // Beast — a 3/3 ground Creature
};

// ── DEPLOYED: On Attack — "You may pay 1 resource. If you do, create a Beast token." ───────────────
// Gated on TOTAL payment capacity (ready resources + Credits + SEC_122 Droids), never a bare resource
// count: the printed cost says "pay 1 resource", and a player who CAN pay must be offered the choice.
// With no way to pay, no offer is raised at all.
$onAttackAbilities["HMW_010:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (SWUTotalPaymentCapacity(intval($player)) < 1) return;
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1,
        tooltip: "Pay_1_resource_to_create_a_Beast_token?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "HMW_010#1", 1);
};

$customDQHandlers["HMW_010#1"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;                       // declined → nothing paid, nothing created
    global $playerID; $playerID = intval($player);
    if (!SWUPayInlineAbilityCost(intval($player), 1)) return;  // "IF YOU DO" — no payment, no Beast
    SWUCreateUnitToken(intval($player), 'HMW_T03');
};
