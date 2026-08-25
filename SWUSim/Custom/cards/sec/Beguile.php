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
    // "Look at AN OPPONENT's hand. Then, choose a non-leader unit THAT OPPONENT controls…" — one opponent
    // is named ONCE and BOTH clauses hang off it. The caster picks WHICH; the picker auto-resolves to an
    // invisible PASSPARAMETER at one eligible opponent, so Premier is byte-identical (I1).
    //
    // ⚠ NO $eligible FILTER. The first clause is a LOOK, and a look ALWAYS resolves — even against an
    // empty hand, and even against an opponent who controls nothing bounceable. So no opponent can be
    // filtered out as unaffected, and choosing a seat purely for the information is a legal line that a
    // filter would have deleted. (Contrast LAW_216, where the chosen player must ACT.)
    // ⚠ The pick must come BEFORE the look — you cannot look at "an opponent's" hand until one is named.
    SWUQueueChooseOpponent(intval($player), 'SEC_233#0|' . $mzID,
        "Choose_an_opponent_whose_hand_to_look_at");
};

$customDQHandlers["SEC_233#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $mzID = (string)($parts[0] ?? '');
    $opp  = SWUPickedOpponent($lastDecision);
    if ($opp <= 0 || $opp === intval($player)) return;
    // The look is a SEPARATE clause and INFORMATION-ONLY — it takes nothing, discards nothing, and is not
    // conditional on the bounce finding a target. It was missing entirely at first (reported as "Beguile
    // not showing cards in hand") because the card's only decision is over ARENA units, and the client
    // reveals a hand only while the viewer has a pending decision naming that hand. Queue it FIRST: the
    // information matters before the bounce, since it is what you are choosing against.
    // ⚠ Passing $opp is what makes the helper emit p{n}Hand-N above two seats — the form the transport's
    //   hidden-zone reveal needs, or the row renders as CARD BACKS and the "look" shows nothing.
    SWULookAtOpponentHand(intval($player), null, $opp);      // game-log entry for the reveal
    SWUQueueShowOpponentHand(intval($player), $opp);         // acknowledge popup — the actual "look"
    // ⚠ 'ofSeat' scopes the bounce pool to THE CHOSEN opponent. 'side' => 'their' alone fans out across
    //   EVERY opponent above two seats, so the caster could look at seat 3's hand and then bounce seat
    //   4's unit — the sweep's over-wide-pool defect, invisible because the pool GREW rather than shrank.
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'BOUNCE_UNIT', 'side' => 'their', 'ofSeat' => $opp, 'nonLeader' => true,
        'extraFilter' => fn($o) => intval(CardCost($o->CardID ?? '')) <= 6,
        'prompt' => "Return_an_enemy_non-leader_unit_(cost_6_or_less)_to_hand",
    ]);
};
