<?php
// IC27_126
// Cost 5 - Saw Gerrera - For the Cause - [Aggression,Aggression] - Unit (Ground) 6/5 (unique)
//   Traits: Rebel
// Text: On Attack: You may double another friendly unit's power for this phase.
//
// An optional pick over the OTHER friendly units (team units in Team Suns — "friendly"), both arenas, leader units
// included. Doubling is a MULTIPLICATIVE modifier (CR 15.2), so it is a marker token rather than a +X snapshot:
// _SWUPowerMultiplier applies it after every addition and subtraction and on every read, so a buff given later in
// the phase, Raid and "+N for this attack" are all doubled as well. Each application is its own stacking token,
// so a unit doubled twice in a phase has ×4. The marker rides the unit (arena move, control change) and expires
// with the phase like any phase-duration turn effect.
// Tests: SWUSim/Tests/Cases/ic27/SawGerrera_ForTheCause.md
$onAttackAbilities["IC27_126:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $pool = _SWUCollectUnitTargets(intval($player), ['side' => 'friendly', 'excludeUID' => intval($self->UniqueID ?? 0)]);
    SWUQueueMayChooseTarget(intval($player), $pool, "Double_another_friendly_unit's_power_for_this_phase?",
        "Choose_another_friendly_unit_to_double_its_power", "IC27_126#0");
};

$customDQHandlers["IC27_126#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision) || SWUObjGone(GetZoneObject($lastDecision))) return;
    AddTurnEffect($lastDecision, _SWUStackingStatToken($lastDecision, 'IC27_126'));
};
