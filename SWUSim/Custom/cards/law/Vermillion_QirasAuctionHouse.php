<?php
// LAW_215
// Cost 6 - Vermillion - Qi'ra's Auction House - [Cunning,Villainy] - Power 5 - HP 7
// Text: When Attack Ends: If this unit survived, reveal the top card of a deck, then choose a player. They may play the revealed card for free. If they do, a different player creates Credit tokens equal to that card's cost.

$onAttackEndAbilities["LAW_215:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $V = intval($player);
    // "the top card of a deck" — own deck or ANY opponent's (Twin Suns; 2-player → self + the one opp).
    $decks = [];
    if (_SWUTopDeckFrontIdx($V) !== -1) $decks[] = $V;
    foreach (OpponentsOf($V) as $o) { if (_SWUTopDeckFrontIdx($o) !== -1) $decks[] = $o; }
    if (empty($decks)) return;                         // all decks empty → fizzle
    if (count($decks) === 1) { _SWUVermillionReveal($V, $decks[0]); return; }
    DecisionQueueController::AddDecision($V, "OPTIONCHOOSE", SWUDeckPickerLabels($V, "Yours&Theirs"), 1,
        tooltip: "Reveal_the_top_card_of_which_deck?");
    DecisionQueueController::AddDecision($V, "CUSTOM", "LAW_215#0|{$V}", 1);
};

$customDQHandlers["LAW_215#0"] = function($player, $parts, $lastDecision) {
    $V = intval($parts[0] ?? $player);
    $D = SWUDecodeDeckPick($lastDecision, $V); // Yours/Your_deck→self, Theirs/Opponent's_deck/P{n}_deck→that player
    _SWUVermillionReveal($V, $D);
};

$customDQHandlers["LAW_215#1"] = function($player, $parts, $lastDecision) {
    $V = intval($parts[0] ?? $player); $D = intval($parts[1] ?? $player); $cardID = $parts[2] ?? '';
    $P = SWUDecodePlayerPick($lastDecision, $V); // "You"→Vermillion's controller, "Opponent"/"P{n}"→that player
    global $playerID; $playerID = $P;
    DecisionQueueController::AddDecision($P, "YESNO", "-", 1,
        tooltip: "Play_" . str_replace(' ', '_', CardTitle($cardID)) . "_for_free?");
    DecisionQueueController::AddDecision($P, "CUSTOM", "LAW_215#2|{$D}|{$P}|{$cardID}", 1);
};

$customDQHandlers["LAW_215#2"] = function($player, $parts, $lastDecision) {
    $D = intval($parts[0] ?? $player); $P = intval($parts[1] ?? $player); $cardID = $parts[2] ?? '';
    if ($lastDecision !== 'YES') return;               // declined → no play, no credits
    global $playerID; $playerID = $P;
    $idx = _SWUTopDeckFrontIdx($D);
    if ($idx === -1 || (GetDeck($D)[$idx]->CardID ?? '') !== $cardID) return; // revealed card no longer on top
    // $D (the deck's owner) is already known here, so name the seat: a literal "theirDeck-N" resolves
    // to SEAT 2 above two seats, which would play a card off the WRONG player's deck.
    $deckMz = ($D === $P) ? "myDeck-{$idx}" : SWUForeignMzID($P, $D, 'Deck', $idx);
    $type   = CardType($cardID);
    if (strpos($type, 'Upgrade') !== false) {
        $hosts = SWUGetUpgradeValidTargets($P, $cardID);
        if (empty($hosts)) return;                     // no valid host → can't play → no credits
        SWUQueueChooseTarget($P, $hosts, "Attach_" . str_replace(' ', '_', CardTitle($cardID)) . "_for_free",
            "LAW_215#3|{$D}|{$P}|{$cardID}|{$deckMz}");
        return;
    }
    // A revealed PILOTING unit may be played as a Pilot upgrade — offer the Unit-vs-Pilot choice, but only
    // when P has a valid Vehicle host. The play is FREE, so query hosts ignoring affordability (ignoreCost).
    if (CardPilotingCost($cardID) !== null && !empty(SWUGetPilotValidTargets($P, $cardID, true))) {
        DecisionQueueController::AddDecision($P, "OPTIONCHOOSE", "Unit&Pilot", 1,
            tooltip: "Play_" . str_replace(' ', '_', CardTitle($cardID)) . "_as_Unit_or_Pilot?");
        DecisionQueueController::AddDecision($P, "CUSTOM", "LAW_215#2P|{$D}|{$P}|{$cardID}|{$deckMz}", 1);
        return;
    }
    if (_SWUPlayForeignResourceFree($P, $D, $deckMz, $cardID, $type)) {
        SWUCreateCreditToken(SWUChooseOpponent($P), intval(CardCost($cardID))); // "a different player" creates Credits = cost (Twin Suns: a null-safe other seat)
    }
};

// LAW_215 revealed PILOTING unit — the Unit-vs-Pilot choice. "Pilot" → pick a Vehicle host (free);
// anything else → play it as a unit (the normal foreign free-play). Credits fire in either branch's finish.
$customDQHandlers["LAW_215#2P"] = function($player, $parts, $lastDecision) {
    $D = intval($parts[0] ?? $player); $P = intval($parts[1] ?? $player); $cardID = $parts[2] ?? ''; $deckMz = $parts[3] ?? '';
    global $playerID; $playerID = $P;
    if ($lastDecision === 'Pilot') {
        $vehicles = SWUGetPilotValidTargets($P, $cardID, true); // free play → ignore affordability
        if (empty($vehicles)) return;
        SWUQueueChooseTarget($P, $vehicles,
            "Attach_" . str_replace(' ', '_', CardTitle($cardID)) . "_as_a_Pilot_for_free",
            "LAW_215#3P|{$D}|{$P}|{$cardID}|{$deckMz}");
        return;
    }
    // "Unit" — play the revealed card as a unit (as before).
    if (_SWUPlayForeignResourceFree($P, $D, $deckMz, $cardID, CardType($cardID))) {
        SWUCreateCreditToken(SWUChooseOpponent($P), intval(CardCost($cardID)));
    }
};

// LAW_215 pilot host chosen — attach the revealed Piloting unit as a free Pilot upgrade; the "different
// player" creates Credits equal to the card's (unit) cost.
$customDQHandlers["LAW_215#3P"] = function($player, $parts, $lastDecision) {
    $D = intval($parts[0] ?? $player); $P = intval($parts[1] ?? $player); $cardID = $parts[2] ?? ''; $deckMz = $parts[3] ?? '';
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = $P;
    AddGlobalEffects($P, 'SWU_CARDS_PLAYED');
    AddGameLogEntry('PLAY', 'P' . $P . ' played ' . GameLogCardRef($cardID) . ' as a Pilot for free');
    _SWUFinalizeUpgradeAttach($P, $cardID, $deckMz, $lastDecision, 0, true, true, true); // ignoreCost, isPilot, suppress after-action
    SWUCreateCreditToken(SWUChooseOpponent($P), intval(CardCost($cardID)));
};

$customDQHandlers["LAW_215#3"] = function($player, $parts, $lastDecision) {
    $D = intval($parts[0] ?? $player); $P = intval($parts[1] ?? $player); $cardID = $parts[2] ?? ''; $deckMz = $parts[3] ?? '';
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = $P;
    AddGlobalEffects($P, 'SWU_CARDS_PLAYED');
    AddGameLogEntry('PLAY', 'P' . $P . ' played ' . GameLogCardRef($cardID) . ' for free');
    _SWUFinalizeUpgradeAttach($P, $cardID, $deckMz, $lastDecision, 0, true, false, true); // ignoreCost, suppress after-action
    SWUCreateCreditToken(SWUChooseOpponent($P), intval(CardCost($cardID))); // "a different player" (null-safe)
};
