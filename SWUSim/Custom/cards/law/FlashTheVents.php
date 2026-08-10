<?php
// LAW_205
// Cost 1 - Flash the Vents - [Aggression]
// Text: Attack with a unit. It gets +2/+0 and gains Overwhelm for this attack. After completing this attack, if that unit damaged a base, defeat that unit.

// LAW_205 Flash the Vents — attack with the chosen unit; +2/+0 and Overwhelm for this attack, and a
// self-defeat-if-it-damaged-a-base marker (consumed in SWUCollectCombatHitTriggers).
$customDQHandlers["LAW_205#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $savedPID = $playerID; $playerID = intval($player);
    $attackerMzID = $lastDecision ?? '';
    $attacker = (!empty($attackerMzID) && str_contains($attackerMzID, '-')) ? GetZoneObject($attackerMzID) : null;
    if (SWUObjGone($attacker)) { $playerID = $savedPID; SWUAfterAction($player); return; }
    SWUAddAttackPowerBonus($attackerMzID, 2);
    AddTurnEffect($attackerMzID, SWUMakeTurnEffect('OVERWHELM', [], SWU_DUR_ATTACK, 'LAW_205'));
    AddTurnEffect($attackerMzID, SWUMakeTurnEffect('LAW_205', [], SWU_DUR_ATTACK));
    // "if that unit damaged a base" covers ability damage to EITHER base, not just this attack's combat
    // damage — and the engine's per-unit base-damage stamps are phase-scoped, so snapshot them here (the
    // attack has not started yet) and let the attack-end check compare. See _SWUUnitBaseDamageStamps.
    $atkUID205 = intval($attacker->UniqueID ?? 0);
    SetSWUVar('SWU_LAW205_UID',  strval($atkUID205));
    SetSWUVar('SWU_LAW205_MARK', strval(_SWUUnitBaseDamageStamps(
        intval($attacker->Controller ?? $player), $atkUID205)));
    BeginSWUAttack($player, $attackerMzID);
    $playerID = $savedPID;
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LAW_205:0"] = function($player, $mzID = '') {
// Flash the Vents — "Attack with a unit. It gets +2/+0 and gains Overwhelm for
                          // this attack. After completing this attack, if that unit damaged a base,
                          // defeat that unit."
            global $playerID; $playerID = intval($player);
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
            SWUQueueChooseTarget(intval($player), $readyUnits, "Choose_a_unit_to_attack_with_(+2/+0,_Overwhelm)", "LAW_205#0");
            return;
};
