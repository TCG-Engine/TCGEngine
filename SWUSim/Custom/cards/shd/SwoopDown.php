<?php
// SHD_230
// Cost 1 - Swoop Down - [Cunning]
// Text: Attack with a space unit. It gains Saboteur and can attack ground units for this attack. If it attacks a ground unit, it gets +2/+0 and the defender gets -2/-0 for this attack.

// ── JTL_015 Rio Durant (leader action: attack with a space unit, +1/+0 + Saboteur this attack) ──────
// $lastDecision = the chosen space unit. Grant the per-attack effects then begin the attack
// (BeginSWUAttack owns the combat continuation / after-action).
// SHD_230 Swoop Down — grant the per-attack marker (Saboteur + cross-arena + conditional +2/-2) to the
// chosen space unit, then begin its attack (BeginSWUAttack owns the combat continuation / after-action).
$customDQHandlers["SHD_230#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    AddTurnEffect($lastDecision, 'SHD_230');   // Saboteur + cross-arena-to-ground + conditional buff/debuff, this attack
    BeginSWUAttack(intval($player), $lastDecision);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_230:0"] = function($player, $mzID = '') {
// Swoop Down — "Attack with a space unit. It gains Saboteur and can attack ground
                          // units for this attack. If it attacks a ground unit, it gets +2/+0 and the
                          // defender gets -2/-0 for this attack." Grant the SHD_230 marker (Saboteur +
                          // cross-arena + conditional buff/debuff) to the chosen space unit, then attack.
            global $playerID; $playerID = intval($player);
            $units = array_values(array_filter(ZoneSearch('mySpaceArena', AnyUnitFilter),
                fn($mz) => ($o = GetZoneObject($mz)) !== null && empty($o->removed) && intval($o->Status) === 1));
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, 'Attack_with_a_space_unit', 'SHD_230#0');
            return;
};
