<?php
// LAW_007
// Cost 5 - Boba Fett - Krayt's Claw Commander - [Command,Villainy] - Power 3 - HP 6
// Text: When a friendly Bounty Hunter unit's attack ends: If the defending unit was defeated, you may exhaust this leader. If you do, create a Credit token.
// DeployText: Raid 1 (This unit gets +1/+0 while attacking.) / When a friendly Bounty Hunter unit's attack ends: If the defending unit was defeated, create a Credit token.
// Epic Action: If you control 5 or more resources, deploy this leader.

// ── LAW_007 Boba Fett ─────────────────────────────────────────────────────────
// Combat observer in CombatLogic (Bounty-Hunter attack ends + defender defeated). The leader-form
// may-exhaust→Credit resolves here:
$customDQHandlers["LAW_007#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    _SWUExhaustUndeployedLeader(intval($player), 'LAW_007');
    SWUCreateCreditToken(intval($player), 1);
};
