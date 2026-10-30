<?php
// IC27_144
// Cost 4 - Jango Fett - Straight Shooter - [Cunning,Villainy] - Unit (Ground) 4/4 (unique)
//   Traits: Separatist, Bounty Hunter
// Text: Ambush
//       When Defeated: You may discard a card from your hand. If you do, return an event from your discard pile to
//       your hand.
//
// Ambush is printed (auto-wired). When Defeated: the SEC_197 Furtive Handmaiden shape — an optional pick over the
// resolving player's hand, then the payoff. The discard happens FIRST, so the card just discarded is itself
// returnable when it is an event. "Return an event" is mandatory over the events in the resolver's OWN discard
// pile (a single event auto-resolves). The resolver is the controller at defeat (a No Glory, Only Results taker
// discards from and returns to THEIR zones); Jango himself goes to his owner's pile, and is a unit anyway.
// The offer is skipped when it could only throw a card away: no event in the discard pile and none in hand.
// Tests: SWUSim/Tests/Cases/ic27/JangoFett_StraightShooter.md
$whenDefeatedAbilities["IC27_144:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $hand = ZoneSearch("myHand");   // empty hand: SWUQueueMayChooseTarget offers nothing
    if (empty(ZoneSearch('myDiscard', ['Event'])) && empty(ZoneSearch('myHand', ['Event']))) return;
    SWUQueueMayChooseTarget(intval($player), $hand, "Discard_a_card_to_return_an_event_from_your_discard_pile?",
        "Choose_a_card_to_discard", "IC27_144#0");
};

$customDQHandlers["IC27_144#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    DoDiscardCard(intval($player), $lastDecision);
    $playerID = intval($player);
    $events = ZoneSearch('myDiscard', ['Event']);
    if (empty($events)) return;
    SWUQueueChooseTarget(intval($player), $events, "Return_an_event_from_your_discard_pile_to_your_hand", "IC27_144#1");
};

$customDQHandlers["IC27_144#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision) || SWUObjGone(GetZoneObject($lastDecision))) return;
    SWUReturnFromDiscardToHand(intval($player), $lastDecision);
};
