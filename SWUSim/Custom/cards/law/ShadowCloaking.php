<?php
// LAW_043
// Cost 5 - Shadow Cloaking - [Vigilance,Aggression,Villainy]
// Text: Ready a unit and give a Shield token to it.

// LAW_043 Shadow Cloaking — step 0: ready the chosen unit and give it a Shield token.
$customDQHandlers["LAW_043#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    OnReadyCard(intval($player), $lastDecision);
    DoGiveShieldToken(intval($player), $lastDecision);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LAW_043:0"] = function($player, $mzID = '') {
// Shadow Cloaking — "Ready a unit and give a Shield token to it." Any unit is
                          // a legal target (friendly or enemy).
            global $playerID; $playerID = intval($player);
            $units = SWUAllUnits();
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Ready_a_unit_and_give_it_a_Shield", "LAW_043#0");
            return;
};
