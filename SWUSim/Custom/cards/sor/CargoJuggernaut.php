<?php
// SOR_068  |  Reprints: SHD_066
// Cost 6 - Cargo Juggernaut - [Vigilance] - Power 4 - HP 6
// Text: Shielded (When you play this unit, give a Shield token to it.) / When Played: If you control another [Vigilance] unit, heal 4 damage from your base.

$whenPlayedAbilities["SOR_068:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $self    = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self);
    $others  = [];
    foreach (array_merge(
        ZoneSearch('myGroundArena', AnyUnitFilter),
        ZoneSearch('mySpaceArena',  AnyUnitFilter)
    ) as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (intval($o->UniqueID ?? -2) === $selfUID) continue;                      // "another"
        if (strpos(CardAspect($o->CardID) ?? '', 'Vigilance') !== false) { $others[] = $mz; break; }
    }
    if (empty($others)) return;
    DecisionQueueController::AddDecision($player, 'PASSPARAMETER', 'myBase-0', 1);
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'HEAL_TARGET|4', 1);
};

// ─── SHD_066 Cargo Juggernaut ─────────────────────────────────────────────────
// Shielded (auto) + When Played: If you control another Vigilance unit, heal 4 damage from your base.
$whenPlayedAbilities["SHD_066:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self, 0);
    $gate = false;
    foreach (GetUnitsInPlay(intval($player)) as $u) {
        if (empty($u->removed) && intval($u->UniqueID ?? 0) !== $selfUID
            && strpos(CardAspect($u->CardID ?? '') ?? '', 'Vigilance') !== false) { $gate = true; break; }
    }
    if ($gate) OnHealBase(intval($player), intval($player), 4);
};
