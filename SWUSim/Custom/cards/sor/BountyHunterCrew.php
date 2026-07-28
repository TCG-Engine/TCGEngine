<?php
// SOR_183
// Cost 6 - Bounty Hunter Crew - [Cunning,Villainy] - Power 4 - HP 4
// Text: Ambush (After you play this unit, it may ready and attack an enemy unit.) / When Played: You may return an event from a discard pile to its owner's hand.

// SOR_183 Bounty Hunter Crew — "When Played: You may return an event from a discard pile to its
// owner's hand." Any discard pile (both players'); the event returns to its OWNER's hand.
$whenPlayedAbilities["SOR_183:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $events = array_values(array_merge(
        ZoneSearch('myDiscard',    ['Event']),
        ZoneSearch('theirDiscard', ['Event'])
    ));
    SWUQueueMayChooseTarget(intval($player), $events,
        'Return_an_event_from_a_discard_pile_to_its_owner\'s_hand', 'Choose_an_event_to_return', 'SOR_183#0');
};

$customDQHandlers["SOR_183#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '' || $lastDecision === '-' || $lastDecision === 'PASS') return;
    SWUReturnDiscardCardToOwnerHand(intval($player), $lastDecision);
};
