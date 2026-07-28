<?php
// SOR_214
// Cost 1 - Smuggling Compartment - [Cunning] - Upgrade Power 1 - Upgrade HP 1
// Text: Attach to a VEHICLE unit. / Attached unit gains: "On Attack: Ready a resource."

// SOR_214 Smuggling Compartment — Upgrade grants the host: "On Attack: Ready a resource."
// Auto-readies the first exhausted resource (mirrors SOR_189).
$onAttackAbilities["SOR_214:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $resources = GetResources($player);
    for ($i = 0; $i < count($resources); $i++) {
        if (!empty($resources[$i]->removed)) continue;
        if (intval($resources[$i]->Status) === 0) {
            DecisionQueueController::AddDecision($player, 'PASSPARAMETER', "myResources-{$i}", 1);
            DecisionQueueController::AddDecision($player, 'CUSTOM', 'READY_RESOURCE', 1);
            return;
        }
    }
};
