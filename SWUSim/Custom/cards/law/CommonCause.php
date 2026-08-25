<?php
// LAW_167
// Cost 2 - Common Cause - [Command]
// Text: Give a unit +1/+1 for this phase for each different aspect among units you control.

// LAW_167 Common Cause — buff the chosen unit +N/+N where N = distinct aspects among units you control.
$customDQHandlers["LAW_167#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $caster = intval($parts[0] ?? intval($player));
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $aspects = [];
    foreach (GetUnitsInPlay($caster) as $u) {
        if (!empty($u->removed)) continue;
        foreach (explode(',', (string)(CardAspect($u->CardID ?? '') ?? '')) as $a) {
            $a = trim($a);
            if ($a !== '') $aspects[$a] = true;
        }
    }
    $n = count($aspects);
    if ($n <= 0) return;
    SWUApplyPhaseBuff($lastDecision, $n, $n, 'LAW_167');
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LAW_167:0"] = function($player, $mzID = '') {
// Common Cause — "Give a unit +1/+1 for this phase for each different aspect
                          // among units you control." Amount computed at resolution (LAW_167#0).
            global $playerID; $playerID = intval($player);
            $units = SWUAllUnits();
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Give_a_unit_+1/+1_per_different_aspect_you_control", "LAW_167#0|" . intval($player));
            return;
};
