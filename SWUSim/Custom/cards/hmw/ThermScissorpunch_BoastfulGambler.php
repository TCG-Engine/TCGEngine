<?php
// HMW_223
// Cost 2 - Therm Scissorpunch - Boastful Gambler - [Cunning] - Unit (Ground) 5/5 - Trait: Underworld - Unique
// Text: When the action phase starts: Reveal the top card of your deck and an opponent's deck. For each
//       card that cost 3 or more revealed this way, this unit gets -2/-2 for this phase.
//
// An ACTION-phase-start trigger, hung off ActionPhaseStart (call site there; body here, mirroring the
// HMW_147 Beast Lair sibling).
//
// Three things this card gets to be careful about:
//   * REVEAL IS NOT DRAW OR MILL. Both cards stay exactly where they are; only their COST is read, and
//     the reveal is logged publicly (contrast Thrawn SOR_016, who LOOKS and whose log line is scoped
//     to him alone).
//   * "your deck" is the CONTROLLER's deck, so a stolen Therm reads its new controller's — and the
//     other half is "an OPPONENT's", relative to that same controller.
//   * "For EACH card" scales: 0 / 1 / 2 qualifying reveals ⇒ -0 / -2/-2 / -4/-4. Applied as ONE
//     debuff of 2N rather than N separate -2/-2 tokens, so the Active Effects chip reads the real
//     total. (SWUApplyPhaseDebuff's stacking token would make repeated application work too, but a
//     single application cannot be mis-read as the de-duping SEC_081 Partagaz bug.)
//
// COST IS PRINTED cost — the revealed card is never played, so no discount or alternate price applies,
// and a Token Unit's 0 never qualifies.

// The top (first non-removed) card of a seat's deck, or null for an empty deck. An empty deck simply
// reveals nothing and contributes 0 — it is not an error and does not skip the other half.
if (!function_exists('_SWUHmw223TopCardID')) {
    function _SWUHmw223TopCardID(int $seat): ?string {
        foreach (GetDeck($seat) as $c) {
            if (!empty($c->removed)) continue;
            return (string)($c->CardID ?? '');
        }
        return null;
    }
}

// The whole effect for ONE Therm, once the opponent whose deck is revealed is known. Both entry paths
// (the 2-player inline call and the Twin Suns opponent-picker continuation) funnel through here so the
// two can never drift.
if (!function_exists('_SWUHmw223Resolve')) {
    function _SWUHmw223Resolve(int $controller, int $thermUID, int $oppSeat): void {
        global $playerID;
        $qualifying = 0;
        foreach ([$controller, $oppSeat] as $deckOwner) {
            $top = _SWUHmw223TopCardID($deckOwner);
            if ($top === null || $top === '') continue;   // empty deck: nothing revealed from this one
            AddGameLogEntry('REVEAL', GameLogCardRef('HMW_223') . ' reveals the top of P' . $deckOwner
                . "'s deck: " . GameLogCardRef($top));    // public: "reveal", not "look"
            if (intval(CardCost($top)) >= 3) $qualifying++;
        }
        if ($qualifying <= 0) return;

        $playerID = $controller;                          // frame the mzID for AddTurnEffect
        $mz = SWUFindMzByUID($thermUID);
        if ($mz === null) return;                         // left play between the reveal and now
        $amount = 2 * $qualifying;
        SWUApplyPhaseDebuff($mz, $amount, $amount, 'HMW_223');   // also runs the shrink-defeat sweep
    }
}

// Twin Suns continuation: the picked seat arrives as "P{n}" in $lastDecision; the Therm's UniqueID
// rides the handler Param (positional mzIDs would be stale by the time the pick is answered).
$customDQHandlers["HMW_223#OPP"] = function ($player, $parts, $lastDecision) {
    $opp = SWUPickedOpponent($lastDecision);
    if ($opp <= 0) return;
    _SWUHmw223Resolve(intval($player), intval($parts[0] ?? 0), $opp);
};

// One trigger per Therm in play, per seat. Unique, so at most one per player — but BOTH players can
// control one, and each reveals from its own controller's perspective.
function _SWUHmw223ActionPhaseTriggers(): void {
    global $playerID;
    $saved = $playerID;
    foreach (GetLiveSeatsArray() as $p) {
        $playerID = $p;
        foreach (GetUnitsInPlay($p) as $u) {
            if (($u->CardID ?? '') !== 'HMW_223') continue;
            if (LostAbilities($u)) continue;              // blanked: no reveal, no debuff
            $uid = intval($u->UniqueID ?? 0);
            if (SeatCountForGame() > 2) {
                // "AN opponent's deck" is a real choice with 2+ opponents. In 2 player the same seam
                // would PASSPARAMETER the lone opponent, but a lone CUSTOM queued on an idle seat is
                // not guaranteed to drain — so 2 player stays fully inline below.
                SWUQueueChooseOpponent($p, "HMW_223#OPP|{$uid}", "Reveal_which_opponent's_top_card?");
                continue;
            }
            _SWUHmw223Resolve($p, $uid, GetOpponent($p));
            $playerID = $p;                               // the resolver leaves it on the controller
        }
    }
    $playerID = $saved;
}
