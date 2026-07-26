<?php
// SHD_179
// Cost 1 - Desperate Attack - [Aggression]
// Text: Attack with a damaged unit. It gets +2/+0 for this attack.

// ─── SHD_179 Desperate Attack (Event) continuation ────────────────────────────
// Attack with the chosen damaged unit; +2/+0 for this attack.
$customDQHandlers["SHD_179#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    SWUAddAttackPowerBonus($lastDecision, 2);
    BeginSWUAttack(intval($player), $lastDecision);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_179:0"] = function($player, $mzID = '') {
// Desperate Attack — "Attack with a damaged unit. It gets +2/+0 for this attack."
            $targets = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $z) {
                foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && intval($o->Status ?? 0) === 1 && intval($o->Damage ?? 0) > 0) $targets[] = $mz;
                }
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Attack_with_a_damaged_unit_(+2/+0)", "SHD_179#0");
            return;
};
