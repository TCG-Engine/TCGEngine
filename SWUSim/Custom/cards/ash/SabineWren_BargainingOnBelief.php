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
    $opp = OtherPlayer($player);
    $sp = $playerID; $playerID = $opp;
    $oppUnits = array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter));
    $playerID = $sp;
    if (empty($oppUnits)) { SWUAfterAction($player); return; }   // opponent controls no unit → nothing happens
    if (count($oppUnits) === 1) {
        // Forced single target — resolve inline (no cross-player decision needed).
        $playerID = $opp;
        DoGiveAdvantageToken($opp, $oppUnits[0]);
        DoGiveAdvantageToken($opp, $oppUnits[0]);
        $playerID = $player;
        AddGlobalEffects($player, 'SWU_ASH006_SHIELDED_NEXT');   // "If they do"
        SWUAfterAction($player);
        return;
    }
    $playerID = $opp;   // multiple units → the opponent picks one of THEIR units
    DecisionQueueController::AddDecision($opp, "MZCHOOSE", implode('&', $oppUnits), 1, tooltip: "Give_2_Advantage_to_a_unit_you_control");
    DecisionQueueController::AddDecision($opp, "CUSTOM", "ASH_006#0|{$player}", 1);
    $playerID = $player;
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
