<?php
// ASH_018
// Cost 4 - Grogu - Charming Companion - [Cunning,Heroism] - Power 0 - HP 3
// Text: / When you play a <uq> unit that costs 4 or more: If this leader is ready, you may deploy him.
// DeployText: While another friendly unit is defending, it gets +1/+0. / While another friendly unit is attacking, the defending unit gets -1/-0.

$customDQHandlers["ASH_018#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (($lastDecision ?? '') !== 'YES') return;
    SWUDeployLeader(intval($player), 'Unit');   // the ASH_018 gate branch only requires Grogu ready
};
