<?php
// IC27_046
// Cost 4 - Vigilant Defense - [Vigilance,Vigilance] - Event, Trait: Tactic (non-unique)
// Text: Give a friendly unit +0/+3 and Sentinel for this phase.
//       Heal 3 damage from a friendly unit or base.
//       Defeat an enemy unit with 3 or less remaining HP.
//
// THREE INDEPENDENT mandatory clauses, chained like IC27_168 Cunning Ploy: each clause builds its pool when it
// runs (so it sees the board the previous clause left), and a clause with no legal target falls straight through
// to the next. "friendly" spans the team — units AND bases (a teammate's base is a friendly base); "enemy" is the
// 'their' fan-out. +0/+3 is the registered IC27_046 STAT_BUFF and Sentinel the ready-made SENTINEL grant, both for
// this phase. "remaining HP" = ObjectCurrentHP - Damage, read when clause 3 runs.
// Tests: SWUSim/Tests/Cases/ic27/VigilantDefense.md

// ── Clause 3: "Defeat an enemy unit with 3 or less remaining HP." ─────────────
function _SWUIc27046Defeat(int $player): void {
    global $playerID; $playerID = $player;
    $pool = [];
    foreach (_SWUCollectUnitTargets($player, ['side' => 'their']) as $mz) {
        $o = GetZoneObject($mz);
        if (intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0) <= 3) $pool[] = $mz;
    }
    if (empty($pool)) return;
    SWUQueueChooseTarget($player, $pool, 'Defeat_an_enemy_unit_with_3_or_less_remaining_HP', 'DEFEAT_UNIT');
}

// ── Clause 2: "Heal 3 damage from a friendly unit or base." ──────────────────
function _SWUIc27046Heal(int $player): void {
    global $playerID; $playerID = $player;
    $pool = _SWUCollectUnitTargets($player, ['side' => 'friendly']);
    $pool[] = 'myBase-0';
    foreach (SWUTeammatesOf($player) as $mate) $pool[] = "p{$mate}Base-0";
    SWUQueueChooseTarget($player, $pool, 'Heal_3_damage_from_a_friendly_unit_or_base', 'IC27_046#1');
}

// ── Clause 1: "Give a friendly unit +0/+3 and Sentinel for this phase." ───────
$whenPlayedAbilities["IC27_046:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $pool = _SWUCollectUnitTargets(intval($player), ['side' => 'friendly']);
    if (empty($pool)) { _SWUIc27046Heal(intval($player)); return; }   // fizzle -> clause 2
    SWUQueueChooseTarget(intval($player), $pool, 'Give_a_friendly_unit_+0/+3_and_Sentinel_for_this_phase', 'IC27_046#0');
};

$customDQHandlers["IC27_046#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!SWUDecisionDeclined($lastDecision) && !SWUObjGone(GetZoneObject($lastDecision))) {
        SWUApplyPhaseBuff($lastDecision, 0, 3, 'IC27_046');
        AddTurnEffect($lastDecision, 'SENTINEL');
    }
    _SWUIc27046Heal(intval($player));
};

$customDQHandlers["IC27_046#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!SWUDecisionDeclined($lastDecision)) {
        if (strpos((string)$lastDecision, 'Base') !== false) {
            $tp = SWUMzOwner((string)$lastDecision, intval($player));
            if ($tp > 0) OnHealBase(intval($player), $tp, 3);
        } elseif (!SWUObjGone(GetZoneObject($lastDecision))) {
            OnHealUnit(intval($player), $lastDecision, 3);
        }
    }
    _SWUIc27046Defeat(intval($player));
};
