<?php
// IC27_042
// Cost 8 - Obi-Wan Kenobi - I sense a Trap - [Vigilance,Heroism] - Power 5 - HP 9
// Traits: Force, Jedi, Republic - Unique
// Text: When Played: You may choose one:
//       • Play a unit that was defeated this phase from your discard pile. It costs [5 resources] less.
//       • Choose a unit that entered play this phase. It gets -5/-5 for this phase.
//
// "You may choose one" = an OPTIONCHOOSE of the viable modes plus Pass (LAW_178 Persecutor's decline). A mode
// that could only fizzle is left off (the house modal rule, HMW_221 Teeka). Mode 2 is in practice always
// viable — Obi-Wan himself has entered play by the time his When Played resolves, and "a unit" includes him.
//
// Mode 1 is TWI_189 Unnatural Life's pool at -5 with no riders (no "enters ready", no regroup defeat). The pool
// is priced by SWUPlayablesAtDiscount — the same pipeline the play charges, aspect penalty and Credits included —
// with Exploit counted, since SWUNestedPlayUnit plays through SWUBeginPlayCard, which offers it. "Defeated this
// phase" needs the owner's SWU_DEFEATED_CARD_ multiset AND a discard entry that left play this round (From='PLAY',
// Turn = the current round, both stamped by SWUAddToDiscard). The multiset is keyed by CardID, so on its own it
// also admits a same-named copy defeated in an earlier round or discarded another way. A round is one action
// phase then one regroup, so for a unit played in the action phase "this round" is "this phase".
// It is "your discard pile", so a defeated unit you own but an opponent controlled qualifies, and an opponent's
// unit you controlled does not (it went to its owner's discard).
//
// Mode 2 is TWI_052 Hello There at -5/-5, read with SWUUnitEnteredPlayThisPhaseAnySeat: the text is the whole
// table, and a unit that entered then changed control still entered play this phase.
//
// The discount rides the continuation's Param, so it survives the request boundary between the pick and the play.
// Tests: SWUSim/Tests/Cases/ic27/ObiWanKenobi_ISenseATrap.md

if (!function_exists('_SWUIc27042DiscardPool')) {
    function _SWUIc27042DiscardPool(int $player, int $discount): array {
        global $playerID, $gTurnNumber; $playerID = $player;
        $out = [];
        foreach (SWUPlayablesAtDiscount($player, 'myDiscard', AnyUnitFilter, $discount, null, true) as $mz) {
            $o = GetZoneObject($mz);
            if (($o->From ?? '') !== 'PLAY') continue;
            if (intval($o->Turn ?? 0) !== intval($gTurnNumber)) continue;   // an earlier round's copy
            if (GlobalEffectCount($player, 'SWU_DEFEATED_CARD_' . ($o->CardID ?? '')) <= 0) continue;
            $out[] = $mz;
        }
        return $out;
    }
    function _SWUIc27042EnteredPool(int $player): array {
        return _SWUCollectUnitTargets($player, [
            'side'        => 'any',
            'extraFilter' => fn($o) => intval($o->Controller ?? 0) > 0 && SWUUnitEnteredPlayThisPhaseAnySeat($o),
        ]);
    }
}

$whenPlayedAbilities["IC27_042:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $modes = [];
    if (!empty(_SWUIc27042DiscardPool(intval($player), 5))) $modes[] = 'PlayFromDiscard';
    if (!empty(_SWUIc27042EnteredPool(intval($player))))   $modes[] = 'EnteredPlayDebuff';
    if (empty($modes)) return;
    $modes[] = 'Pass';
    DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", implode('&', $modes), 1, tooltip: "You_may_choose_one");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "IC27_042#0", 1);
};

// Mode chosen → raise that mode's pick (recomputed now, not trusted from the menu).
$customDQHandlers["IC27_042#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision) || $lastDecision === 'Pass') return;
    global $playerID; $playerID = intval($player);
    if ($lastDecision === 'PlayFromDiscard') {
        $pool = _SWUIc27042DiscardPool(intval($player), 5);
        if (empty($pool)) return;
        SWUQueueChooseTarget(intval($player), $pool, "Play_a_unit_defeated_this_phase_(costs_5_less)", "IC27_042#1|5");
    } elseif ($lastDecision === 'EnteredPlayDebuff') {
        $pool = _SWUIc27042EnteredPool(intval($player));
        if (empty($pool)) return;
        SWUQueueChooseTarget(intval($player), $pool, "Give_a_unit_that_entered_this_phase_-5/-5", "APPLY_PHASE_DEBUFF|5|5|IC27_042");
    }
};

// Mode 1 — play the chosen unit through the full play ceremony (its When Played, additional costs, Exploit).
// Obi-Wan's own play owns this action's ending; the nested frame refuses the inner play's close (as HMW_204).
$customDQHandlers["IC27_042#1"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || !preg_match('/myDiscard-(\d+)/', (string) $lastDecision)) return;
    global $playerID; $playerID = intval($player);
    SWUNestedPlayUnit(intval($player), strval($lastDecision), intval($parts[0] ?? 0));
};
