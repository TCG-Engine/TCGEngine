<?php
// SOR_186
// Cost 2 - No Good to Me Dead - [Cunning,Villainy]
// Text: Exhaust a unit. That unit can't ready this round (including during the regroup phase).

// SOR_186 No Good to Me Dead — exhaust the chosen unit and flag it "can't ready this round" by its
// UniqueID on its controller (consumed at the next regroup ready step). Already-exhausted target is
// fine: the exhaust is a no-op but the flag still locks it out of the regroup ready.
$customDQHandlers["SOR_186#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $uid  = intval($o->UniqueID ?? 0);
    $ctrl = intval($o->Controller ?? 0);
    OnExhaustCard(intval($player), $lastDecision);
    if ($uid > 0 && $ctrl > 0) AddGlobalEffects($ctrl, 'SWU_CANT_READY_' . $uid);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_186:0"] = function($player, $mzID = '') {
// No Good to Me Dead — "Exhaust a unit. That unit can't ready this round
                          // (including during the regroup phase)." Any unit (already-exhausted is a
                          // legal target — the exhaust no-ops but the can't-ready flag still applies).
            global $playerID;
            $playerID = intval($player);
            $targets = SWUAllUnits();
            SWUQueueChooseTarget(intval($player), $targets, "Exhaust_a_unit_(it_can't_ready_this_round)", "SOR_186#0");
            return;
};
