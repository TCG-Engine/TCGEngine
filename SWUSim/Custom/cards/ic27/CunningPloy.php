<?php
// IC27_168
// Cost 4 - Cunning Ploy - [Cunning,Cunning] - Event, Trait: Trick (non-unique)
// Text: Look at an opponent's hand. You may discard a card from it. If you do, that player draws a card.
//       Exhaust an enemy unit.
//       You may attack with a unit. It gets +3/+0 for this attack.
//
// THREE INDEPENDENT clauses (the LOF_223 Force Illusion family): a clause with no legal target must
// fizzle WITHOUT skipping the ones after it. They are chained through continuations so the order is
// deterministic and every fizzle path still falls through to the next clause — each clause helper is
// therefore called from BOTH its predecessor's continuation and its predecessor's no-target branch.
//
// Only the DRAW is conditional: "If you do, that player draws a card" hangs off the discard actually
// happening, so declining the discard must not draw. The exhaust is mandatory; the attack is a "may".

// ── Clause 2: "Exhaust an enemy unit." ───────────────────────────────────────
function Ic27168ExhaustEnemy(int $player): void {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if (!SWUObjGone($o)) $targets[] = $mz;
        }
    }
    if (empty($targets)) { Ic27168OfferAttack(intval($player)); return; }   // fizzle -> clause 3
    SWUQueueChooseTarget(intval($player), $targets, "Exhaust_an_enemy_unit", "IC27_168#1");
}

// ── Clause 3: "You may attack with a unit. It gets +3/+0 for this attack." ───
function Ic27168OfferAttack(int $player): void {
    global $playerID; $playerID = intval($player);
    $ready = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
        $arr = GetZone($zone);
        for ($i = 0; $i < count($arr); $i++) {
            $u = $arr[$i];
            if (SWUObjGone($u)) continue;
            if (intval($u->Status) === 1) $ready[] = "{$zone}-{$i}";   // only a READY unit can attack
        }
    }
    if (empty($ready)) return;
    SWUQueueMayChooseTarget(intval($player), $ready,
        "Attack_with_a_unit_(+3/+0)?", "Choose_a_unit_to_attack_with_(+3/+0)", "IC27_168#2");
}

// ── Clause 1: look at the opponent's hand, may discard, and only then they draw ──
$whenPlayedAbilities["IC27_168:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    // SWULookAtOpponentHand both REVEALS the hand to the caster and returns its theirHand-N mzIDs.
    $targets = SWULookAtOpponentHand(intval($player));
    if (empty($targets)) { Ic27168ExhaustEnemy(intval($player)); return; }   // fizzle -> clause 2
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Discard_a_card_from_the_opponent's_hand?",
        "Discard_a_card_from_the_opponent's_hand", "IC27_168#0");
};

$customDQHandlers["IC27_168#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!SWUDecisionDeclined($lastDecision)) {
        $obj = GetZoneObject($lastDecision);
        if (!SWUObjGone($obj)) {
            // Mirrors DISCARD_FROM_OPP_HAND so the forced discard fires the same observers.
            $opp    = OtherPlayer(intval($player));
            $cardID = $obj->CardID;
            $obj->Remove();
            SWUAddToDiscard($opp, $cardID, 'HAND');
            DecisionQueueController::CleanupRemovedCards();
            AddGameLogEntry('DISCARD', 'P' . intval($player) . ' discarded ' . GameLogCardRef($cardID) . " from P{$opp}'s hand");
            DoDrawCard($opp, 1);   // "If you do, that player draws a card."
        }
    }
    Ic27168ExhaustEnemy(intval($player));
};

$customDQHandlers["IC27_168#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!SWUDecisionDeclined($lastDecision)) {
        $o = GetZoneObject($lastDecision);
        if (!SWUObjGone($o)) OnExhaustCard(intval($player), $lastDecision);
    }
    Ic27168OfferAttack(intval($player));
};

$customDQHandlers["IC27_168#2"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    SWUAddAttackPowerBonus($lastDecision, 3);   // +3/+0 for THIS attack (one-shot, not a phase buff)
    // No SWUAfterAction: this is an event, and its FINISH_PLAY_CARD finalizes the action (BeginSWUAttack
    // owns the combat continuation).
    BeginSWUAttack(intval($player), $lastDecision);
};
