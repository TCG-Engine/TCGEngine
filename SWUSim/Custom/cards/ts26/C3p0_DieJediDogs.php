<?php
// TS26_15
// Cost 2 - C-3P0 - Die, Jedi Dogs! - [Vigilance,Command] - Power 2 - HP 5
// Text: When Played: An opponent takes control of this unit. / Action [Exhaust]: Deal damage equal to this unit's power to another ground unit. Only opponents may use this ability.

// TS26_15 C-3P0 — When Played: an opponent takes control of this unit (permanent — no reversion clause).
// In 2P the single opponent takes control. SWUTakeControlOfUnit moves it into their arena while PRESERVING
// Owner, so the "only opponents may use" gate (SWUUnitActionAffordable) still identifies the original owner.
// CLAUSE 1 (converted 2026-08-23): "An opponent takes control of this unit" — the CASTER picks which
// opponent. Auto-resolves to an invisible PASSPARAMETER at one eligible opponent, so Premier is
// byte-identical (I1). No $eligible filter: any live opponent can take control of a unit.
// SWUTakeControlOfUnit moves it into their arena while PRESERVING Owner, which is what lets the
// "only opponents may use" gate still identify the original owner.
//
// CLAUSE 2 (converted 2026-08-24): "Only opponents may use this ability" = opponents of the unit's
// CURRENT CONTROLLER (USER RULING). Ownership is irrelevant. The gate lives in SWUUnitActionAffordable's
// TS26_15 case; the ability itself is registered in $anyPlayerUnitActions so it is offered on a unit the
// actor does NOT control.
// ⚠ At TWO seats the readings pick OPPOSITE players (owner-gate ⇒ the controller fires it; controller-gate
// ⇒ the original owner does), so this corrected Premier behaviour too.
$whenPlayedAbilities["TS26_15:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // Carry the unit by UID: the pick is interactive, and a positional mzID can shift before it resolves.
    $self = GetZoneObject($mzID);
    if (SWUObjGone($self)) return;
    $uid = intval($self->UniqueID ?? 0);
    if ($uid <= 0) return;
    SWUQueueChooseOpponent(intval($player), 'TS26_15#0|' . $uid,
        "Choose_an_opponent_to_take_control_of_C-3PO");
};

$customDQHandlers["TS26_15#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $uid = intval($parts[0] ?? 0);
    $opp = SWUPickedOpponent($lastDecision);
    if ($uid <= 0 || $opp <= 0 || $opp === intval($player)) return;
    $mz = SWUFindMzByUID($uid);
    if ($mz === null) return;                      // left play while the pick was open
    SWUTakeControlOfUnit($opp, $mz);
};

// TS26_15 C-3P0 — Action [Exhaust]: deal damage equal to this unit's power to another ground unit. The
// Exhaust cost is paid by SWUUnitAction (default 'exhaust' cost kind); the "only opponents may use"
// restriction is the owner-gate in SWUUnitActionAffordable. "Another" excludes C-3P0 itself; either side.
// "Only opponents may use this ability" means the actor is NEVER the controller, so the action has to be
// surfaced on a unit the actor does not control — that is exactly what $anyPlayerUnitActions does
// (SWUComputeActionsData offers those on other seats' boards). Without this the gate would permit the
// click but the offer path would never show it.
$anyPlayerUnitActions['TS26_15'] = true;

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
