<?php
// SEC_169
// Cost 5 - AAT Incinerator - [Aggression] - Power 3 - HP 6
// Text: When Played: Deal 1 damage to each of up to 4 other ground units. If no friendly units were damaged by this ability, deal 2 damage to your base.

// SEC_169 AAT Incinerator — When Played: deal 1 to each of up to 4 OTHER ground units. If no friendly
// units were damaged by this ability, deal 2 to your own base.
$whenPlayedAbilities["SEC_169:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self, 0);
    $ground = [];
    foreach (SWUAllUnits(null, GroundArena) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $selfUID) $ground[] = $mz;
    }
    if (empty($ground)) { SWUDealDamageToBase(2, intval($player)); return; }   // nothing to hit → self-base penalty
    DecisionQueueController::AddDecision(intval($player), "MZMULTICHOOSE", "0|4|" . implode('&', $ground), 1, tooltip: "Deal_1_to_each_of_up_to_4_other_ground_units");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SEC_169#0|" . intval($player), 1);
};

$customDQHandlers["SEC_169#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $picks = ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS')
        ? array_slice(array_values(array_filter(explode('&', (string)$lastDecision), fn($m) => $m !== '' && $m !== '-')), 0, 4) : [];
    $damagedFriendly = false;
    $uids = [];
    foreach ($picks as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (intval($o->Controller ?? 0) === intval($player)) $damagedFriendly = true;
        $uids[] = intval($o->UniqueID ?? 0);
    }
    foreach ($uids as $uid) { $mz = SWUFindMzByUID($uid); if ($mz !== null) SWUDealDamageToUnit($mz, 1, intval($player)); }
    if (!$damagedFriendly) SWUDealDamageToBase(2, intval($player));   // "if no friendly units were damaged"
};
