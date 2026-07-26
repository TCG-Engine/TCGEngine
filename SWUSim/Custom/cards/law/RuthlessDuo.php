<?php
// LAW_137
// Cost 4 - Ruthless Duo - [Command,Villainy] - Power 3 - HP 5
// Text: When Played: If you control another Villainy unit, you may deal 2 damage to a ground unit.

// LAW_137 Ruthless Duo — When Played: if you control another Villainy unit, you may deal 2 to a ground unit.
$whenPlayedAbilities["LAW_137:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $uid  = SWUObjUID($self, 0);
    $has = false;
    foreach (GetUnitsInPlay(intval($player)) as $u) {
        if (!empty($u->removed) || intval($u->UniqueID ?? 0) === $uid) continue;
        if (strpos((string)(CardAspect($u->CardID ?? '') ?? ''), 'Villainy') !== false) { $has = true; break; }
    }
    if (!$has) return;
    $ground = SWUAllUnits(null, GroundArena);
    if (empty($ground)) return;
    SWUQueueMayChooseTarget(intval($player), $ground, "Deal_2_to_a_ground_unit?", "Choose_a_ground_unit", "DEAL_UNIT_DAMAGE|2");
};
