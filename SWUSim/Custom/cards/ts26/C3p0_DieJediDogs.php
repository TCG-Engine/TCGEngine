<?php
// TS26_15
// Cost 2 - C-3P0 - Die, Jedi Dogs! - [Vigilance,Command] - Power 2 - HP 5
// Text: When Played: An opponent takes control of this unit. / Action [Exhaust]: Deal damage equal to this unit's power to another ground unit. Only opponents may use this ability.

// TS26_15 C-3P0 — When Played: an opponent takes control of this unit (permanent — no reversion clause).
// In 2P the single opponent takes control. SWUTakeControlOfUnit moves it into their arena while PRESERVING
// Owner, so the "only opponents may use" gate (SWUUnitActionAffordable) still identifies the original owner.
// NOTE (multiplayer design fork): C-3P0 is a Twin Suns multiplayer-politics card — "an opponent takes
// control" (you'd choose WHICH opponent) and "only opponents may use" (any opponent, not you) both assume
// 3-4 players. This branch has no N-player helpers, so this is the 2P degenerate reading (the one opponent
// gains control and is the only one who can activate the ping). Flagged for the user, not blocking.
$whenPlayedAbilities["TS26_15:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUTakeControlOfUnit(OtherPlayer(intval($player)), $mzID);
};

// TS26_15 C-3P0 — Action [Exhaust]: deal damage equal to this unit's power to another ground unit. The
// Exhaust cost is paid by SWUUnitAction (default 'exhaust' cost kind); the "only opponents may use"
// restriction is the owner-gate in SWUUnitActionAffordable. "Another" excludes C-3P0 itself; either side.
$unitAbilities["TS26_15"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    if (SWUObjGone($self)) { SWUAfterAction(intval($player)); return; }
    $selfUID = intval($self->UniqueID ?? -1);
    $pow = intval(ObjectCurrentPower($self));
    $targets = [];
    foreach (['myGroundArena', 'theirGroundArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $m) {
            $o = GetZoneObject($m);
            if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? -1) !== $selfUID) $targets[] = $m;
        }
    }
    if (empty($targets) || $pow <= 0) { SWUAfterAction(intval($player)); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Deal_{$pow}_damage_to_another_ground_unit", "DEAL_UNIT_DAMAGE|{$pow}");
    SWUQueueAfterAction(intval($player));
};
