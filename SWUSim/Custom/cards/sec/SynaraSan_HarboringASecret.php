<?php
// SEC_225
// Cost 7 - Synara San - Harboring a Secret - [Cunning] - Power 7 - HP 7
// Text: Hidden / On Attack: For each friendly unit, ready a friendly resource.

// SEC_225 Synara San — Hidden + On Attack: for each friendly unit, ready a friendly resource.
$onAttackAbilities["SEC_225:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $n = 0;
    foreach (GetUnitsInPlay(intval($player)) as $u) { if (empty($u->removed)) $n++; }
    $res = &GetResources(intval($player));
    $readied = 0;
    for ($i = 0; $i < count($res) && $readied < $n; $i++) {
        if (empty($res[$i]->removed) && intval($res[$i]->Status ?? 0) === 0) { $res[$i]->Status = 1; $readied++; }
    }
};
