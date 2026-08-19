<?php
// SEC_233
// Beguile
// Text: Look at an opponent's hand. Then, choose a non-leader unit that opponent controls that costs 6 or less and return it to its owner's hand.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_233:0"] = function($player, $mzID = '') {
    // Beguile — Look at an opponent's hand; choose a non-leader enemy unit that costs 6 or less and
    // return it to its owner's hand.
    //
    // The look is a SEPARATE clause and it was missing entirely: the only choice this card raises is
    // over units in the ARENA, and the client only reveals an opponent's hand while the viewer has a
    // pending decision whose param names `theirHand` (see the Visibility=Self reveal flag). So the
    // player got the bounce and never saw the hand — reported as "Beguile not showing cards in hand".
    // The information matters BEFORE the bounce (you are choosing what to tempo out), so queue the
    // reveal first; SWUQueueShowOpponentHand no-ops on an empty hand.
    SWULookAtOpponentHand(intval($player));      // game-log entry for the reveal
    SWUQueueShowOpponentHand(intval($player));   // acknowledge popup — the actual "look"
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'BOUNCE_UNIT', 'side' => 'their', 'nonLeader' => true,
        'extraFilter' => fn($o) => intval(CardCost($o->CardID ?? '')) <= 6,
        'prompt' => "Return_an_enemy_non-leader_unit_(cost_6_or_less)_to_hand",
    ]);
};
