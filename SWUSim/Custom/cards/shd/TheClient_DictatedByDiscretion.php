<?php
// SHD_031
// Cost 3 - The Client - Dictated by Discretion - [Villainy,Vigilance] - Power 2 - HP 5
// Text: Shielded / Action [Exhaust]: Choose a unit. For this phase, it gains: "Bounty - Heal 5 damage from a base." (When that unit is defeated or captured, its opponent collects its bounty.)

// ─── SHD_031 The Client ───────────────────────────────────────────────────────
// Action [Exhaust]: Choose a unit. For this phase, it gains: "Bounty — Heal 5 damage from a base."
// Grants the phase-duration BOUNTY token 'SHD_031' (registry row in GameLogic.php); the reward is
// collected on the bountied unit's defeat via the granted-bounty snapshot + SWUCollectBounty (exact
// SHD_006 mirror, no dash param). The Client is itself a unit, so a valid target always exists.
$unitActionCostKind["SHD_031"] = 'exhaust';

$unitAbilities["SHD_031"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = _SWUShd006AllUnits(intval($player));
    if (empty($targets)) { SWUAfterAction(intval($player)); return; } // defensive
    SWUQueueChooseTarget(intval($player), $targets, "Choose_a_unit_to_give_a_Bounty", "SHD_031#0");
};

$customDQHandlers["SHD_031#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        $o = GetZoneObject($lastDecision);
        if ($o !== null && empty($o->removed)) {
            AddTurnEffect($lastDecision, SWUMakeTurnEffect('SHD_031'));
        }
    }
    SWUAfterAction(intval($player));
};
