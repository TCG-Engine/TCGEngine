<?php
// HMW_044
// Cost 3 - Ima-Gun Di, Holding Out For Resupply - [Command][Aggression][Heroism] - Unit (Ground) 5/3 - unique
// Traits: Force, Jedi, Republic
// Text: When Defeated: If you control fewer resources than an opponent, you may resource a card from
//       your hand. If you do, resource the top card of your deck.
//
// "AN OPPONENT" HERE IS AN EXISTENTIAL CONDITION, NOT A TARGET. It is true when ANY live opponent
// controls more, so this card must NEVER raise a seat prompt — adding one would be its own bug (the
// documented "that player / an opponent" split). LAW_083 Broken Horn prints the identical sentence and
// is the shape copied here: OpponentsOf() rather than OtherPlayer(), which at four seats answers 2 for
// seat 1 and 1 for everyone else and would interrogate exactly one of three opponents. OpponentsOf also
// filters to LIVE seats, so an eliminated seat's abandoned resource pile cannot satisfy the condition,
// and it excludes a teammate in Team Suns — which is the correct reading of "opponent" either way.
//
// "FEWER" IS STRICT: equal counts do not qualify (Boundary_EqualResources_DoesNotFire).
//
// "RESOURCES YOU CONTROL" IS THE WHOLE ZONE, ready and exhausted alike — SWUResourceCount with no
// $readyOnly, which also skips Credit tokens (CR 3.13). Same reading as LAW_083 and HMW_046.
//
// WHO IS "YOU" — the controller at the moment the When Defeated resolves, which is not always the
// player who played the card. Under JTL_043 No Glory, Only Results the thief takes control and then
// defeats it, so all three seat-scoped readings ("you control", "your hand", "your deck") belong to the
// NEW controller. $player is the defeated unit's controller, so this is correct for free — the section
// exists to keep it that way.
$whenDefeatedAbilities["HMW_044:0"] = function($player, $mzID = '') {
    global $playerID;
    $playerID = intval($player);
    $me       = intval($player);

    $maxRes = 0;
    foreach (OpponentsOf($me) as $o) $maxRes = max($maxRes, SWUResourceCount($o));
    if (SWUResourceCount($me) >= $maxRes) return;

    // An empty hand is NOT a decline: there is nothing to offer, so no prompt is raised at all and the
    // "If you do" rider never becomes reachable. That comes from SWUQueueMayChooseTarget, which returns
    // on an empty pool — an early return here as well is dead code (measured: deleting it left the suite
    // green), and the guarantee is pinned by EmptyHand_NoPromptAtAll rather than by a line in this file.
    $hand = ZoneSearch("myHand");

    SWUQueueMayChooseTarget($me, $hand,
        "Resource_a_card_from_your_hand?", "Choose_a_card_to_resource", "HMW_044#0");
};

// ── The optional resource-from-hand, and the "If you do" rider hanging off it ──────────────────────
$customDQHandlers["HMW_044#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $me       = intval($player);

    // "If you do" — a DECLINE takes the top-deck resource down with it. Nothing below may run.
    if (SWUDecisionDeclined($lastDecision)) return;

    // Status 0 = EXHAUSTED. A plain "resource a card" carries no "and ready it" rider (contrast
    // HMW_123 Wookiee Warriors and TS26_12 Sundari Palace, which say so explicitly).
    if (SWURampResourceExhausted($me, (string)$lastDecision) === null) return;
    SWUKeepCreditTokensLast($me);

    // MEASURE, don't assume: the rider fires only because the hand card actually moved above.
    $deck = ZoneSearch("myDeck", null);
    // Empty deck → the rider is a clean no-op and the hand card already resourced above still stands.
    // This guard is for the $deck[0] read, not for the outcome: MZMove no-ops on a missing mzID anyway,
    // so removing it does not change behaviour (measured). EmptyDeck_HandCardIsStillResourced is what
    // actually pins the outcome.
    if (empty($deck)) return;
    SWURampResourceExhausted($me, $deck[0]);
    SWUKeepCreditTokensLast($me);
};
