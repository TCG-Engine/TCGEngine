<?php
// TS26_69
// Cost 2 - Remove the Chip - [Aggression]
// Text: Deal 2 damage to a unit. If it's a Clone, ready it.

// TS26_69 Remove the Chip — deal 2 to the chosen unit; if it survives and is a Clone, ready it.
$customDQHandlers["TS26_69#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    $uid = intval(GetZoneObject($lastDecision)->UniqueID ?? 0);
    SWUDealDamageToUnit($lastDecision, 2, intval($player));
    // Re-find it by UID: a defeated unit is removed at once, so the old mzID would name the unit behind it.
    $mz = $uid > 0 ? SWUFindMzByUID($uid) : null;
    $o = $mz !== null ? GetZoneObject($mz) : null;
    if ($o !== null && empty($o->removed) && TraitContains($o, 'Clone')) OnReadyCard(intval($player), $mz);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TS26_69:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $tg = SWUAllUnits();
    if (empty($tg)) return;
    SWUQueueChooseTarget(intval($player), $tg, "Deal_2_damage_to_a_unit", "TS26_69#0");
};
