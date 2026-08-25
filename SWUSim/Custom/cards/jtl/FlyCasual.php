<?php
// JTL_206
// Cost 1 - Fly Casual - [Cunning,Heroism]
// Text: Ready a Vehicle unit. It can't attack bases for this phase.

// ── JTL_206 Fly Casual (event continuation) — ready the chosen Vehicle; it can't attack bases this phase.
$customDQHandlers["JTL_206#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision) || $lastDecision === '') return;
    global $playerID;
    $playerID = intval($player);
    OnReadyCard(intval($player), $lastDecision);
    AddTurnEffect($lastDecision, 'CANT_ATTACK_BASES');
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_206:0"] = function($player, $mzID = '') {
// Fly Casual — ready a Vehicle unit; it can't attack bases for this phase.
            global $playerID;
            $playerID = intval($player);
            $targets = [];
            foreach (SWUAllUnits() as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && HasTrait($o->CardID, 'Vehicle')) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Ready_a_Vehicle_unit_(can't_attack_bases_this_phase)", "JTL_206#0");
            return;
};
