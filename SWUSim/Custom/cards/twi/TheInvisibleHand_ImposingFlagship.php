<?php
// TWI_234
// Cost 8 - The Invisible Hand - Imposing Flagship - [Villainy] - Power 4 - HP 7
// Text: When Played: Create 4 Battle Droid tokens. / On Attack: Exhaust any number of friendly Separatist units. Deal 1 damage to the defending player's base for each unit exhausted this way.

// TWI_234 The Invisible Hand — "When Played: Create 4 Battle Droid tokens.
//   On Attack: Exhaust any number of friendly Separatist units. Deal 1 damage to the defending
//   player's base for each unit exhausted this way."
$whenPlayedAbilities["TWI_234:0"] = function($player, $mzID) {
    SWUCreateUnitTokens(intval($player), 'TWI_T01', 4);
};

$onAttackAbilities["TWI_234:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $attacker = GetZoneObject($mzID);
    $attackerUID = ($attacker !== null) ? intval($attacker->UniqueID ?? -1) : -1;
    $specs = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, ['Unit', 'Token Unit', 'Leader Unit']) as $mz) {
            $o = GetZoneObject($mz);
            if (SWUObjGone($o)) continue;
            if (intval($o->Status) !== 1) continue;                       // only ready units can be exhausted
            if (intval($o->UniqueID ?? -2) === $attackerUID) continue;    // not the attacker itself
            if (!HasTrait($o->CardID, 'Separatist')) continue;
            $specs[] = $mz;
        }
    }
    if (empty($specs)) return;                                           // nothing to exhaust → no rider
    DecisionQueueController::AddDecision(intval($player), 'MZMULTICHOOSE',
        '0|' . count($specs) . '|' . implode('&', $specs), 1,
        tooltip: 'Exhaust_any_number_of_friendly_Separatist_units_(1_base_damage_each)');
    DecisionQueueController::AddDecision(intval($player), 'CUSTOM', 'TWI_234#0', 1);
    // Combat owns the after-action.
};

$customDQHandlers["TWI_234#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $picks = ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS')
        ? [] : array_values(array_filter(explode('&', $lastDecision), fn($s) => $s !== '' && $s !== '-' && $s !== 'PASS'));
    $count = 0;
    foreach ($picks as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o) || intval($o->Status) !== 1) continue;
        OnExhaustCard(intval($player), $mz);
        $count++;
    }
    if ($count > 0) SWUDealDamageToBase($count, OtherPlayer(intval($player))); // defending player = opponent
};
