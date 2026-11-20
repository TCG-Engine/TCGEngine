<?php
// IC27_127
// Cost 4 - Act of Aggression - [Aggression,Aggression] - Event, Trait: Innate (non-unique)
// Text: Draw a card.
//       You may deal 3 damage to a unit.
//       Give a friendly unit +3/+0 and Overwhelm for this phase.
//
// Three independent clauses chained like IC27_046 Vigilant Defense: each pool is built when its clause runs (a unit
// the 3 damage defeats is never offered the buff), and a clause with nothing to do falls through to the next.
// Clause 2 is optional (MZMAYCHOOSE over every unit on the table); clause 3 is mandatory over the friendly (team)
// units: the registered IC27_127 STAT_BUFF (+3/+0) and the ready-made OVERWHELM grant, both for this phase.
// Tests: SWUSim/Tests/Cases/ic27/ActOfAggression.md

// ── Clause 3: "Give a friendly unit +3/+0 and Overwhelm for this phase." ─────
function _SWUIc27127Buff(int $player): void {
    global $playerID; $playerID = $player;
    $pool = _SWUCollectUnitTargets($player, ['side' => 'friendly']);
    if (empty($pool)) return;
    SWUQueueChooseTarget($player, $pool, 'Give_a_friendly_unit_+3/+0_and_Overwhelm_for_this_phase', 'IC27_127#1');
}

// ── Clauses 1 and 2: draw, then "You may deal 3 damage to a unit." ───────────
$whenPlayedAbilities["IC27_127:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    DoDrawCard(intval($player), 1);
    $playerID = intval($player);
    $pool = _SWUCollectUnitTargets(intval($player));
    if (empty($pool)) { _SWUIc27127Buff(intval($player)); return; }   // nothing to damage -> clause 3
    SWUQueueMayChooseTarget(intval($player), $pool, 'Deal_3_damage_to_a_unit?', 'Deal_3_damage_to_a_unit', 'IC27_127#0');
};

$customDQHandlers["IC27_127#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!SWUDecisionDeclined($lastDecision) && !SWUObjGone(GetZoneObject($lastDecision))) {
        SWUDealDamageToUnit($lastDecision, 3, intval($player));
    }
    _SWUIc27127Buff(intval($player));
};

$customDQHandlers["IC27_127#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision) || SWUObjGone(GetZoneObject($lastDecision))) return;
    SWUApplyPhaseBuff($lastDecision, 3, 0, 'IC27_127');
    AddTurnEffect($lastDecision, 'OVERWHELM');
};
