<?php
// HMW_149 Log Trap — Event, cost 2, [Command], Trick.
// "Attack with a friendly unit. Then attack with it again, even if it's exhausted. It can't attack bases for
//  the second attack."
// First attack: a friendly unit you control that can attack right now (ready, with a target) — mandatory, bases
// allowed. The unit's UniqueID is armed in SWU_HMW149_AGAIN; the SWU_TRIGGER_RESUME stack-empty branch fires
// the second attack AFTER the first attack's full resolution (including any nested "attack with another unit"
// chain it triggered). The second attack is skipped when "it" no longer exists (defeated, bounced → new UID),
// is no longer controlled by you, can't attack, or has no non-base target — checked up front because
// BeginSWUAttack READIES an attacker it finds no target for.

if (!function_exists('_SWUHmw149FirstAttackers')) {
    function _SWUHmw149FirstAttackers(int $player): array {
        global $playerID; $playerID = $player;
        $units = [];
        foreach (['myGroundArena' => 'GroundArena', 'mySpaceArena' => 'SpaceArena'] as $zone => $arena) {
            $arr = GetZone($zone);
            for ($i = 0; $i < count($arr); $i++) {
                if (_SWUUnitCanAttackNow($player, $arr[$i], $arena)) $units[] = "{$zone}-{$i}";
            }
        }
        return $units;
    }

    // Called from the SWU_TRIGGER_RESUME stack-empty branch once the first attack has fully resolved. Returns
    // true if the second attack began, false if it fizzled (the caller then closes the action). No resume is
    // re-queued: the second combat owns the after-action itself (mutation-verified redundant, 2026-09-16).
    function _SWUHmw149SecondAttack(int $player): bool {
        global $playerID;
        $uid = intval(GetSWUVar('SWU_HMW149_AGAIN', '0'));
        SetSWUVar('SWU_HMW149_AGAIN', '');
        if ($uid <= 0) return false;
        $playerID = $player;
        $mz = SWUFindMzByUID($uid);
        if ($mz === null || strpos($mz, 'my') !== 0) return false;
        $o = GetZoneObject($mz);
        if (SWUObjGone($o) || intval($o->Controller ?? $player) !== $player) return false;
        if (_SWUUnitHardCantAttack($o)) return false;
        if (($o->CardID ?? '') === 'LOF_063' && intval($o->Damage ?? 0) <= 0 && !LostAbilities($o)) return false;
        $arena = (strpos($mz, 'Space') !== false) ? 'SpaceArena' : 'GroundArena';
        if (empty(SWUGetAllValidAttackTargets($player, $o, $arena, true))) return false;
        BeginSWUAttack($player, $mz, true);
        return true;
    }
}

$whenPlayedAbilities["HMW_149:0"] = function($player, $mzID = '') {
    $units = _SWUHmw149FirstAttackers(intval($player));
    if (empty($units)) return;
    SWUQueueChooseTarget(intval($player), $units, 'Choose_a_unit_to_attack_with_(it_attacks_twice)', 'HMW_149#0');
};

$customDQHandlers["HMW_149#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    SetSWUVar('SWU_HMW149_AGAIN', strval(intval($o->UniqueID ?? 0)));
    BeginSWUAttack(intval($player), $lastDecision);
};
