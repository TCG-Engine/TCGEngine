<?php
// TWI_222
// Cost 1 - Political Pressure - [Cunning]
// Text: Choose an opponent. They may discard a random card from their hand. If they don't, create 2 Battle Droid tokens.

// TWI_222 Political Pressure — the opponent answered the "discard a random card?" YESNO. On YES,
// discard a random card from their hand; on NO (they don't), the CASTER creates 2 Battle Droids.
// $parts[0] = the caster's player number (the opponent is the one answering / $player here).
// Runs on the CASTER's queue with the picked seat in $lastDecision ("P{n}"). Queues the chosen
// opponent's own YES/NO onto THEIR queue, in THEIR frame.
$customDQHandlers["TWI_222#1"] = function($player, $parts, $lastDecision) {
    $caster = intval($parts[0] ?? $player);
    $opp    = SWUPickedOpponent($lastDecision);
    if ($opp <= 0 || $opp === $caster) return;
    global $playerID;
    $playerID = $opp;
    $oppHand = ZoneSearch('myHand', null);   // relative to $playerID = the chosen opponent
    // Empty hand ⇒ they cannot discard ⇒ "they don't" ⇒ the caster gets the droids. Resolved without a
    // prompt because there is nothing for them to decide.
    if (empty($oppHand)) { SWUCreateUnitTokens($caster, 'TWI_T01', 2); $playerID = $caster; return; }
    DecisionQueueController::AddDecision($opp, 'YESNO', '-', 1,
        tooltip: 'Discard_a_random_card_or_the_opponent_creates_2_Battle_Droids?');
    DecisionQueueController::AddDecision($opp, 'CUSTOM', 'TWI_222#0|' . $caster, 1);
    $playerID = $caster;
};

$customDQHandlers["TWI_222#0"] = function($player, $parts, $lastDecision) {
    $caster = intval($parts[0] ?? 0);
    $opp    = intval($player); // this handler runs under the opponent (the decider)
    if ($lastDecision === 'YES') {
        global $playerID;
        $playerID = $opp;
        $hand = ZoneSearch('myHand', null);
        if (!empty($hand)) {
            $pick = $hand[array_rand($hand)];
            DoDiscardCard($opp, $pick);
        } else {
            SWUCreateUnitTokens($caster, 'TWI_T01', 2); // no card to discard → they "don't" → droids
        }
    } else {
        SWUCreateUnitTokens($caster, 'TWI_T01', 2);
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_222:0"] = function($player, $mzID = '') {
// Political Pressure — "Choose an opponent. They may discard a random card
                          // from their hand. If they don't, create 2 Battle Droid tokens."
            // "CHOOSE AN OPPONENT" — a real choice above two seats. The picker auto-resolves to a
            // PASSPARAMETER when there is exactly one eligible opponent, so Premier is byte-identical (I1).
            //
            // ⚠⚠ $eligible IS DELIBERATELY null — do NOT pass SWUOpponentsWithCards() here. Everywhere else
            // in the discard family an empty hand is a fizzle and gets filtered out; on THIS card an empty
            // hand is a GUARANTEED PAYOFF the caster may deliberately want: "They MAY discard… IF THEY
            // DON'T, create 2 Battle Droid tokens." Aiming at a hellbent seat is the card's strongest line,
            // and filtering would silently delete it. (See TWI_252, one card away, which needs the OPPOSITE
            // treatment — a shared shape is not a shared eligibility rule.)
            SWUQueueChooseOpponent(intval($player), 'TWI_222#1|' . intval($player),
                "Choose_an_opponent_to_pressure");
            return;
};
