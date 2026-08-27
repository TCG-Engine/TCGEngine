<?php
// LAW_215
// Cost 6 - Vermillion - Qi'ra's Auction House - [Cunning,Villainy] - Power 5 - HP 7
// Text: When Attack Ends: If this unit survived, reveal the top card of a deck, then choose a player. They may play the revealed card for free. If they do, a different player creates Credit tokens equal to that card's cost.

$onAttackEndAbilities["LAW_215:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $V = intval($player);
    // "the top card of A DECK" — unqualified, so EVERY live seat's deck is in the pool, a TEAMMATE's
    // included. This used to walk OpponentsOf($V), which in Team Suns dropped your partner's deck; with
    // only that deck stocked the whole trigger fizzled silently. SWUDeckPickerLabels agrees (all seats).
    $decks = [];
    if (_SWUTopDeckFrontIdx($V) !== -1) $decks[] = $V;
    foreach (GetLiveSeatsArray() as $o) {
        $o = intval($o);
        if ($o === $V) continue;
        if (_SWUTopDeckFrontIdx($o) !== -1) $decks[] = $o;
    }
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
    DecisionQueueController::AddDecision($P, "CUSTOM", "LAW_215#2|{$V}|{$D}|{$P}|{$cardID}", 1);
};

$customDQHandlers["LAW_215#2"] = function($player, $parts, $lastDecision) {
    $V = intval($parts[0] ?? $player); $D = intval($parts[1] ?? $player);
    $P = intval($parts[2] ?? $player); $cardID = $parts[3] ?? '';
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
            "LAW_215#3|{$V}|{$P}|{$cardID}|{$deckMz}");
        return;
    }
    // A revealed PILOTING unit may be played as a Pilot upgrade — offer the Unit-vs-Pilot choice, but only
    // when P has a valid Vehicle host. The play is FREE, so query hosts ignoring affordability (ignoreCost).
    if (CardPilotingCost($cardID) !== null && !empty(SWUGetPilotValidTargets($P, $cardID, true))) {
        DecisionQueueController::AddDecision($P, "OPTIONCHOOSE", "Unit&Pilot", 1,
            tooltip: "Play_" . str_replace(' ', '_', CardTitle($cardID)) . "_as_Unit_or_Pilot?");
        DecisionQueueController::AddDecision($P, "CUSTOM", "LAW_215#2P|{$V}|{$D}|{$P}|{$cardID}|{$deckMz}", 1);
        return;
    }
    if (_SWUPlayForeignResourceFree($P, $D, $deckMz, $cardID, $type)) {
        _SWULaw215Credits($V, $P, $cardID); // "a different player" creates Credits = cost (Twin Suns: a null-safe other seat)
    }
};

// LAW_215 revealed PILOTING unit — the Unit-vs-Pilot choice. "Pilot" → pick a Vehicle host (free);
// anything else → play it as a unit (the normal foreign free-play). Credits fire in either branch's finish.
$customDQHandlers["LAW_215#2P"] = function($player, $parts, $lastDecision) {
    $V = intval($parts[0] ?? $player); $D = intval($parts[1] ?? $player);
    $P = intval($parts[2] ?? $player); $cardID = $parts[3] ?? ''; $deckMz = $parts[4] ?? '';
    global $playerID; $playerID = $P;
    if ($lastDecision === 'Pilot') {
        $vehicles = SWUGetPilotValidTargets($P, $cardID, true); // free play → ignore affordability
        if (empty($vehicles)) return;
        SWUQueueChooseTarget($P, $vehicles,
            "Attach_" . str_replace(' ', '_', CardTitle($cardID)) . "_as_a_Pilot_for_free",
            "LAW_215#3P|{$V}|{$P}|{$cardID}|{$deckMz}");
        return;
    }
    // "Unit" — play the revealed card as a unit (as before).
    if (_SWUPlayForeignResourceFree($P, $D, $deckMz, $cardID, CardType($cardID))) {
        _SWULaw215Credits($V, $P, $cardID);
    }
};

// LAW_215 pilot host chosen — attach the revealed Piloting unit as a free Pilot upgrade; the "different
// player" creates Credits equal to the card's (unit) cost.
$customDQHandlers["LAW_215#3P"] = function($player, $parts, $lastDecision) {
    $V = intval($parts[0] ?? $player); $P = intval($parts[1] ?? $player); $cardID = $parts[2] ?? ''; $deckMz = $parts[3] ?? '';
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = $P;
    AddGlobalEffects($P, 'SWU_CARDS_PLAYED');
    AddGameLogEntry('PLAY', 'P' . $P . ' played ' . GameLogCardRef($cardID) . ' as a Pilot for free');
    _SWUFinalizeUpgradeAttach($P, $cardID, $deckMz, $lastDecision, 0, true, true, true); // ignoreCost, isPilot, suppress after-action
    _SWULaw215Credits($V, $P, $cardID);
};

$customDQHandlers["LAW_215#3"] = function($player, $parts, $lastDecision) {
    $V = intval($parts[0] ?? $player); $P = intval($parts[1] ?? $player); $cardID = $parts[2] ?? ''; $deckMz = $parts[3] ?? '';
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = $P;
    AddGlobalEffects($P, 'SWU_CARDS_PLAYED');
    AddGameLogEntry('PLAY', 'P' . $P . ' played ' . GameLogCardRef($cardID) . ' for free');
    _SWUFinalizeUpgradeAttach($P, $cardID, $deckMz, $lastDecision, 0, true, false, true); // ignoreCost, suppress after-action
    _SWULaw215Credits($V, $P, $cardID); // "a different player" (null-safe)
};

// "If they do, A DIFFERENT PLAYER creates Credit tokens equal to that card's cost."
// ⚠ "A different player" means any player other than $P (the one who played the card) — a TEAMMATE of the
// Vermillion's controller included, which is why this uses SWUQueueChoosePlayer and not
// SWUQueueChooseOpponent (that one starts from OpponentsOf() and drops teammates).
// The chooser is $V, the VERMILLION'S CONTROLLER: the ability is theirs, and nothing in the text hands
// the choice to anyone else. It was $D — the revealed DECK'S owner — which only looks the same when you
// reveal your own deck. Reveal an opponent's and the prompt landed on THEM, and because they are idle at
// that moment their queue never drained in that request, so the Credits silently never appeared. No ruling exists for this card, so this follows the sweep's standing rule
// that the controlling player makes an unassigned choice.
// It used to be SWUChooseOpponent($P) — an AUTO-PICK of the first live opponent of $P, i.e. the sweep's
// original placeholder, which silently removed the choice and named one seat.
function _SWULaw215Credits(int $V, int $P, string $cardID): void {
    $eligible = [];
    foreach (GetLiveSeatsArray() as $seat) if ($seat !== $P) $eligible[] = $seat;
    if (empty($eligible)) return;
    $amt = intval(CardCost($cardID));
    // ⚠ Resolve INLINE when there is no actual choice. This runs while $P's queue is draining, and a lone
    // CUSTOM queued onto an IDLE player ($D) never drains in that request — the credits simply never
    // appeared (measured: five two-seat sections went red). Only defer when a real pick exists.
    if (count($eligible) === 1) {
        if ($amt > 0) SWUCreateCreditToken($eligible[0], $amt);
        return;
    }
    SWUQueueChoosePlayer($V, "LAW_215#CREDITS|" . $amt,
        "Which_player_creates_the_Credit_tokens?", $eligible);
}

$customDQHandlers["LAW_215#CREDITS"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $amt  = max(0, intval($parts[0] ?? 0));
    $seat = SWUPickedOpponent($lastDecision);   // reads any "P{n}" token, not only opponents
    if ($seat > 0 && $amt > 0) SWUCreateCreditToken($seat, $amt);
};
