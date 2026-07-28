<?php
// JTL_244
// Cost 2 - There Is No Escape - [Villainy]
// Text: Choose up to 3 units. Those units lose all abilities and can't gain abilities for this round.

// ── JTL_244 There Is No Escape — the chosen units lose all abilities (this round). ────────────────────
$customDQHandlers["JTL_244#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '' || $lastDecision === '-' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    foreach (explode("&", $lastDecision) as $mz) {
        if ($mz === '' || $mz === '-' || $mz === 'PASS') continue;
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) { AddTurnEffect($mz, 'JTL_244'); _SWUCheckDefeatAfterAbilityLoss($mz); }
    }
};
