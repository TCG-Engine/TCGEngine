<?php
// ASH_006
// Cost 5 - Sabine Wren - Bargaining on Belief - [Vigilance,Heroism] - Power 3 - HP 5
// Text: Action [Exhaust]: An opponent gives 2 Advantage tokens to a unit they control. If they do, the next unit you play this phase gains Shielded for this phase. (When you play that unit, give a Shield token to it.)
// DeployText: On Attack: The next unit you play this phase gains Shielded for this phase.
// Epic Action: If you control 5 or more resources, deploy this leader.

// ASH_006 Sabine Wren — the next unit you play this phase gains Shielded for this phase.
$onAttackAbilities["ASH_006:0"] = function($player, $mzID) {
    AddGlobalEffects(intval($player), 'SWU_ASH006_SHIELDED_NEXT');
};

// ASH_006 Sabine Wren — Action [Exhaust]: an opponent gives 2 Advantage tokens to a unit they control. If
// they do, the next unit you play this phase gains Shielded (SWU_ASH006_SHIELDED_NEXT, applied at entry).
$leaderAbilities["ASH_006"] = function(int $player): void {
    global $playerID; $playerID = $player;
    // "AN opponent gives 2 Advantage tokens to a unit THEY control" — the caster picks which opponent.
    // ⚠ FILTER to opponents controlling at least one unit: the chosen player is asked to DO something with
    // their own board (taxonomy shape 1), and with no unit they cannot act, cannot satisfy "if they do",
    // and cannot grant the Shielded rider — a choice among nothing.
    // ⚠ The gate and SWUAfterAction stay OUTSIDE the picker: SWUQueueChooseOpponent queues NOTHING at zero
    // eligible, so an inside-the-picker after-action would never fire and the action would hang.
    $eligible = [];
    foreach (OpponentsOf($player) as $o) {
        $sp0 = $playerID; $playerID = $o;
        $u = array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter));
        $playerID = $sp0;
        if (!empty($u)) $eligible[] = $o;
    }
    if (empty($eligible)) { SWUAfterAction($player); return; }   // nobody can act → nothing happens
    SWUQueueChooseOpponent($player, 'ASH_006#1|' . $player, "Choose_an_opponent_to_give_2_Advantage", $eligible);
    SWUQueueAfterAction($player);
};

$customDQHandlers["ASH_006#1"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $caster = intval($parts[0] ?? $player);
    $opp    = SWUPickedOpponent($lastDecision);
    if ($opp <= 0 || $opp === $caster) return;
    $playerID = $caster;
    $sp = $playerID; $playerID = $opp;
    $oppUnits = array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter));
    $playerID = $sp;
    // Board may have emptied while the pick was open. The after-action was already queued by the leader
    // ability, so just stop here — do NOT call SWUAfterAction again.
    if (empty($oppUnits)) { $playerID = $caster; return; }
    if (count($oppUnits) === 1) {
        // Forced single target — resolve inline (no cross-player decision needed).
        $playerID = $opp;
        DoGiveAdvantageToken($opp, $oppUnits[0]);
        DoGiveAdvantageToken($opp, $oppUnits[0]);
        $playerID = $caster;
        AddGlobalEffects($caster, 'SWU_ASH006_SHIELDED_NEXT');   // "If they do"
        return;
    }
    $playerID = $opp;   // multiple units → the chosen opponent picks one of THEIR units
    DecisionQueueController::AddDecision($opp, "MZCHOOSE", implode('&', $oppUnits), 1, tooltip: "Give_2_Advantage_to_a_unit_you_control");
    DecisionQueueController::AddDecision($opp, "CUSTOM", "ASH_006#0|" . $caster, 1);
    $playerID = $caster;
};

$customDQHandlers["ASH_006#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);   // $player = the opponent who chose
    $leaderCtrl = intval($parts[0] ?? 0);
    if ($lastDecision && str_contains($lastDecision, '-')) {
        $o = GetZoneObject($lastDecision);
        if ($o !== null && empty($o->removed)) {
            DoGiveAdvantageToken(intval($player), $lastDecision);
            DoGiveAdvantageToken(intval($player), $lastDecision);
            AddGlobalEffects($leaderCtrl, 'SWU_ASH006_SHIELDED_NEXT');   // "If they do"
        }
    }
    SWUAfterAction($leaderCtrl);
};
