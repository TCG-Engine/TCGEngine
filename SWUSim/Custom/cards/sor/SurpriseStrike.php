<?php
// SOR_220  |  Reprints: SHD_231
// Cost 2 - Surprise Strike - [Cunning]
// Text: Attack with a unit. It gets +3/+0 for this attack.

// SOR_220 Surprise Strike — give the chosen attacker +3/+0 for this attack, then attack.
$customDQHandlers["SOR_220#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $savedPID = $playerID;
    $playerID = intval($player);
    $attackerMzID = $lastDecision ?? '';
    $attacker = (!empty($attackerMzID) && str_contains($attackerMzID, '-')) ? GetZoneObject($attackerMzID) : null;
    if (SWUObjGone($attacker)) {
        $playerID = $savedPID;
        SWUAfterAction($player);
        return;
    }
    SWUAddAttackPowerBonus($attackerMzID, 3);  // +3/+0 for THIS attack (one-shot, not a phase buff)
    BeginSWUAttack($player, $attackerMzID);   // handles exhaust + target selection + combat continuation
    $playerID = $savedPID;
};

// ─── SHD_231 Surprise Strike (Event) continuation ─────────────────────────────
$customDQHandlers["SHD_231#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    SWUAddAttackPowerBonus($lastDecision, 3);
    BeginSWUAttack(intval($player), $lastDecision);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_220:0"] = function($player, $mzID = '') {
// Surprise Strike — "Attack with a unit. It gets +3/+0 for this attack."
            global $playerID;
            $playerID = intval($player);
            $readyUnits = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
                $arr = GetZone($zone);
                for ($i = 0; $i < count($arr); $i++) {
                    $u = $arr[$i];
                    if (SWUObjGone($u)) continue;
                    if (intval($u->Status) === 1) $readyUnits[] = "{$zone}-{$i}";
                }
            }
            if (empty($readyUnits)) return;
            SWUQueueChooseTarget(intval($player), $readyUnits, "Choose_a_unit_to_attack_with_(+3/+0)", "SOR_220#0");
            return;
};
