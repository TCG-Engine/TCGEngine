<?php
// TWI_179
// Cost 1 - Soulless One - Customized for Grievous - [Cunning,Villainy] - Power 1 - HP 2
// Text: On Attack: You may exhaust a friendly Droid unit or General Grievous (leader or unit). If you do, this unit gets +2/+0 for this attack.

// TWI_179 Soulless One — "On Attack: You may exhaust a friendly Droid unit or General Grievous (leader
// or unit). If you do, this unit gets +2/+0 for this attack." Offer the eligible ready friendly units;
// exhaust the chosen one and grant Soulless One a one-shot +2/+0.
$onAttackAbilities["TWI_179:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self);
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
        $arr = GetZone($zone);
        for ($i = 0; $i < count($arr); $i++) {
            $u = $arr[$i];
            if (SWUObjGone($u) || intval($u->Status) !== 1) continue;
            if (intval($u->UniqueID ?? -2) === $selfUID) continue;
            if (TraitContains($u, 'Droid') || SWUObjectTitle($u) === 'General Grievous') $targets[] = "{$zone}-{$i}";
        }
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Exhaust_a_friendly_Droid_or_Grievous_(this_unit_+2/+0)?", "Choose_a_Droid_or_Grievous", "TWI_179#0|" . $mzID);
    // Combat owns the after-action.
};

$customDQHandlers["TWI_179#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    OnExhaustCard(intval($player), $lastDecision);
    $selfMz = $parts[0] ?? '';
    if ($selfMz !== '') SWUAddAttackPowerBonus($selfMz, 2); // +2/+0 for this attack
};
