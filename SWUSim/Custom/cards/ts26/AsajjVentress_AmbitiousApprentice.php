<?php
// TS26_07
// Cost 5 - Asajj Ventress - Ambitious Apprentice - [Cunning,Villainy] - Power 3 - HP 5
// Text: Action [Exhaust]: Attack with a token unit. It gets +1/+0 for this attack.
// DeployText: Hidden (This unit can't be attacked if she was deployed this phase.) / While you've attacked with a token unit this phase, this unit gets +2/+0.
// Epic Action: If you control 5 or more resources, deploy this leader.

// TS26_07 Asajj Ventress (front) — the chosen token unit attacks with +1/+0 for this attack.
$customDQHandlers["TS26_07#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) { SWUAfterAction(intval($player)); return; }
    SWUAddAttackPowerBonus($lastDecision, 1);
    BeginSWUAttack(intval($player), $lastDecision);   // combat owns the after-action
};

// TS26_07 Asajj Ventress (front) — Action [Exhaust]: attack with a token unit; it gets +1/+0 for this
// attack. (Deployed side: Hidden auto + a +2/+0 passive while you've attacked with a token this phase.)
$leaderAbilities["TS26_07"] = function(int $player): void {
    global $playerID; $playerID = intval($player);
    $tokens = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, ['Token Unit']) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Status) === 1) $tokens[] = $mz;
        }
    }
    if (empty($tokens)) { SWUAfterAction(intval($player)); return; }
    SWUQueueChooseTarget(intval($player), $tokens, "Attack_with_a_token_unit_(+1/+0)", "TS26_07#0");
};
