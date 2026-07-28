<?php
// TWI_016
// Cost 5 - Jango Fett - Concealing the Conspiracy - [Cunning,Villainy] - Power 3 - HP 7
// Text: When a friendly unit deals damage to an enemy unit: You may exhaust this leader. If you do, exhaust that enemy unit.
// DeployText: When a friendly unit deals damage to an enemy unit: You may exhaust that unit.
// Epic Action: If you control 5 or more resources, deploy this leader.

$customDQHandlers["TWI_016#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return; // declined the "may"
    $enemyUID = intval($parts[0] ?? 0);
    $exhaustLeader = intval($parts[1] ?? 0) === 1;
    if ($enemyUID <= 0) return;
    global $playerID; $playerID = intval($player);
    if ($exhaustLeader) {
        // Front side — the cost is exhausting the (undeployed) leader. A single damage effect can damage
        // several enemies at once (JTL_140/JTL_170), queuing one front offer per enemy while Jango is still
        // ready; only the FIRST accepted one can actually pay. Re-verify the cost here — if Jango is no
        // longer undeployed+ready, the cost can't be paid, so the whole ability does nothing (cost gates
        // before effect). This is what limits the front side to exhausting one enemy per turn.
        $ready = false;
        foreach (GetLeader(intval($player)) as $l) {
            if (!empty($l->removed)) continue;
            if (($l->CardID ?? '') === 'TWI_016') { $ready = (empty($l->Deployed) && !empty($l->Ready)); break; }
        }
        if (!$ready) return;
        _SWUExhaustUndeployedLeader(intval($player), 'TWI_016'); // pay the front-side cost
    }
    $mz = SWUFindMzByUID($enemyUID);
    if ($mz === null) return; // enemy left play meanwhile
    OnExhaustCard(intval($player), $mz);
};
