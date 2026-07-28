<?php
// SOR_103
// Cost 1 - Rebel Assault - [Command,Heroism]
// Text: Attack with a REBEL unit. It gets +1/+0 for this attack. / Then, attack with another REBEL unit. It gets +1/+0 for this attack.

// SOR_103 Rebel Assault — event follow-up (first attacker chosen): +1/+0 to the first attacker,
// arm the chained MANDATORY "then attack with another Rebel" (+1/+0), then begin the first attack.
$customDQHandlers["SOR_103#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    $uid = ($obj !== null) ? intval($obj->UniqueID ?? 0) : 0;
    SWUAddAttackPowerBonus($lastDecision, 1);
    SetSWUVar('SWU_CHAINED_ATTACK', "1,0,1,{$uid}"); // rebelOnly, mandatory, +1
    BeginSWUAttack(intval($player), $lastDecision);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_103:0"] = function($player, $mzID = '') {
// Rebel Assault — "Attack with a Rebel unit. It gets +1/+0 for this attack.
                          //   Then, attack with another Rebel unit. It gets +1/+0 for this attack."
            global $playerID;
            $playerID = intval($player);
            $rebels = array_values(array_filter(array_merge(
                ZoneSearch('myGroundArena', AnyUnitFilter),
                ZoneSearch('mySpaceArena',  AnyUnitFilter)
            ), function($mz) { $o = GetZoneObject($mz); return $o !== null && intval($o->Status) === 1 && TraitContains($o, 'Rebel'); }));
            if (empty($rebels)) return;
            // First attacker → SOR_103 handler (+1/+0, arms the mandatory chained second attack).
            SWUQueueChooseTarget($player, $rebels, 'Attack_with_a_Rebel_unit', 'SOR_103#0');
            return;
};
