<?php
// JTL_097
// Cost 3 - Leia Organa - Pilots, To Your Stations - [Command,Heroism] - Power 3 - HP 4
// Text: Restore 1 (When this unit attacks, heal 1 damage from your base.) / When Played: You may attack with a Pilot unit or a unit with a Pilot on it. It gets +1/+0 and gains Restore 1 for this attack.

// ── JTL_097 Leia Organa — When Played: attack with a Pilot unit. It gets +1/+0 and gains Restore 1 for
// this attack. (Her own Restore 1 keyword is auto-wired.) ──────────────────────────────────────────────
$whenPlayedAbilities["JTL_097:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $pilots = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
        $arr = GetZone($zone);
        for ($i = 0; $i < count($arr); $i++) {
            $u = $arr[$i];
            if (SWUObjGone($u) || intval($u->Status) !== 1) continue;
            $isPilot = HasTrait($u->CardID, 'Pilot');
            if (!$isPilot && !empty($u->Subcards) && is_array($u->Subcards)) {
                foreach ($u->Subcards as $sub) {       // "or a unit with a Pilot on it"
                    $scid = is_array($sub) ? ($sub['CardID'] ?? '') : ($sub->CardID ?? '');
                    $cap  = is_array($sub) ? !empty($sub['IsCaptive']) : !empty($sub->IsCaptive);
                    $pil  = is_array($sub) ? !empty($sub['IsPilot'])   : !empty($sub->IsPilot);
                    if (!$cap && ($pil || ($scid !== '' && HasTrait($scid, 'Pilot')))) { $isPilot = true; break; }
                }
            }
            if ($isPilot) $pilots[] = "{$zone}-{$i}";
        }
    }
    if (empty($pilots)) return;
    SWUQueueMayChooseTarget(intval($player), $pilots,
        "Attack_with_a_Pilot_unit_(+1/+0,_Restore_1)", "Choose_a_Pilot_unit_to_attack_with", "JTL_097#0");
};

$customDQHandlers["JTL_097#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) return;
    SWUAddAttackPowerBonus($lastDecision, 1);                                   // +1/+0 for this attack
    AddTurnEffect($lastDecision, SWUMakeTurnEffect('RESTORE', [1], SWU_DUR_ATTACK, 'JTL_097')); // Restore 1 this attack
    BeginSWUAttack(intval($player), $lastDecision);
};
