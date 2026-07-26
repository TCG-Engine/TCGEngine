<?php
// SOR_009
// Cost 5 - Leia Organa - Alliance General - [Command,Heroism] - Power 3 - HP 6
// Text: Action [exhaust]: Attack with a Rebel unit. Then, you may attack with another Rebel unit.
// DeployText: Raid 1 (This unit gets +1/+0 while attacking.) / When this unit completes an attack: You may attack with another Rebel unit.
// Epic Action: If you control 5 or more resources, deploy this leader.

// SOR_009 Leia Organa — leader-action follow-up (first attacker chosen): arm the chained "you may
// attack with another Rebel" (rebelOnly, may-decline, +0) keyed to exclude the first attacker, then
// begin the first attack. The ChainedAttack trigger fires the optional second attack after it ends.
$customDQHandlers["SOR_009#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') {
        SWUAfterAction($player);
        return;
    }
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    $uid = ($obj !== null) ? intval($obj->UniqueID ?? 0) : 0;
    SetSWUVar('SWU_CHAINED_ATTACK', "1,1,0,{$uid}"); // rebelOnly, may-decline, +0
    BeginSWUAttack(intval($player), $lastDecision);
};

// SOR_009 Leia Organa — Deployed: "When this unit completes an attack: you may attack with another
// Rebel unit." (Her Raid 1 is auto-wired via $Raid_Cards.) Fires after she completes any attack.
$onAttackEndAbilities["SOR_009:0"] = function($player, $mzID) {
    $self = GetZoneObject($mzID);
    $uid  = ($self !== null) ? intval($self->UniqueID ?? 0) : 0;
    SWUQueueAnotherAttack(intval($player), true, true, 0, $uid); // Rebel-only, may decline, +0
};

// SOR_009 Leia Organa — Leader Action [Exhaust]: Attack with a Rebel unit. Then, you may attack
// with another Rebel unit. The SOR_009 handler arms the chained "you may attack with another"
// (rebelOnly, may-decline, +0) before the first attack. Deployed side (Raid 1 + OnAttackEnd) lives
// in CardDQHandlers.php / $Raid_Cards.
$leaderAbilities["SOR_009"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    $rebels = array_values(array_filter(array_merge(
        ZoneSearch('myGroundArena', AnyUnitFilter),
        ZoneSearch('mySpaceArena',  AnyUnitFilter)
    ), function($mz) { $o = GetZoneObject($mz); return $o !== null && intval($o->Status) === 1 && HasTrait($o->CardID, 'Rebel'); }));
    if (empty($rebels)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $rebels, 'Attack_with_a_Rebel_unit', 'SOR_009#0');
};
