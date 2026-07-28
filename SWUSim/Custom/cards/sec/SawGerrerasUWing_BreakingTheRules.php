<?php
// SEC_174
// Cost 6 - Saw Gerrera's U-Wing - Breaking the Rules - [Aggression] - Power 4 - HP 8
// Text: Saboteur / When this unit completes an attack (and survives): You may attack with another Aggression unit.

// SEC_174 Saw Gerrera's U-Wing — Saboteur + "When this unit completes an attack (and survives): you
// may attack with another Aggression unit." onAttackEnd only fires if the attacker survived.
$onAttackEndAbilities["SEC_174:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self, 0);
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
        $arr = GetZone($zone);
        for ($i = 0; $i < count($arr); $i++) {
            $u = $arr[$i];
            if (SWUObjGone($u) || intval($u->Status) !== 1) continue;
            if (intval($u->UniqueID ?? 0) === $selfUID) continue;
            if (strpos(CardAspect($u->CardID ?? '') ?? '', 'Aggression') !== false) $targets[] = "{$zone}-{$i}";
        }
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Attack_with_another_Aggression_unit?", "Choose_a_unit_to_attack_with", "SEC_174#0");
};

$customDQHandlers["SEC_174#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    BeginSWUAttack(intval($player), $lastDecision);
};
