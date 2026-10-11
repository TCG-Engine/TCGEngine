<?php
// JTL_131
// Cost 7 - Turbolaser Salvo - [Command]
// Text: Choose an arena. A friendly space unit deals damage equal to its power to each enemy unit in that arena.

// ── JTL_131 Turbolaser Salvo — arena chosen; pick the friendly space dealer (its power is the AOE). ───
$customDQHandlers["JTL_131#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $arena = ($lastDecision === 'Space') ? 'SpaceArena' : 'GroundArena';
    $dealers = ZoneSearch('mySpaceArena', AnyUnitFilter);
    if (empty($dealers)) return;
    SWUQueueChooseTarget(intval($player), $dealers,
        "A_friendly_space_unit_deals_its_power_to_each_enemy_in_that_arena", "JTL_131#1|{$arena}");
};

// Dealer chosen → deal its power to each enemy unit in the chosen arena (snapshot UIDs, index-shift safe).
$customDQHandlers["JTL_131#1"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $arena = (($parts[0] ?? 'GroundArena') === 'SpaceArena') ? 'SpaceArena' : 'GroundArena';
    $dealer = GetZoneObject($lastDecision);
    if (SWUObjGone($dealer)) return;
    $pow = intval(ObjectCurrentPower($dealer));
    if ($pow <= 0) return;
    $srcTok = _SWUEncodeDamageSource((string)$lastDecision);
    $uids = [];
    foreach (ZoneSearch('their' . $arena, AnyUnitFilter) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID ?? 0);
    }
    // HMW_185 Ty Yorrick — owner ruling 2026-10-07: ONE question for the whole salvo; accepted, EACH enemy unit takes
    // power+1. (Dealt hit by hit through the single-target funnel, it used to ask once per enemy unit.)
    $uidList = implode(',', $uids);
    $ty = _SWUHmw185Decider(intval($player));
    if ($ty > 0 && !empty($uids)) { _SWUHmw185Defer($ty, "JTL_131#2|" . intval($player) . "|{$pow}|{$srcTok}|{$uidList}"); return; }
    _SWUJtl131Salvo(intval($player), $pow, $srcTok, $uids);
};

// Resume after Ty's question. parts: dealer seat | power | source token | target UIDs (,-joined).
$customDQHandlers["JTL_131#2"] = function($player, $parts, $lastDecision) {
    $pow = intval($parts[1] ?? 0) + (_SWUHmw185Accepted($lastDecision) ? 1 : 0);
    $uids = array_map('intval', array_filter(explode(',', (string)($parts[3] ?? ''))));
    _SWUJtl131Salvo(intval($parts[0] ?? 0), $pow, (string)($parts[2] ?? ''), $uids);
};

// Deal $pow to each target by UID (index-shift safe). The dealer is the damage's SOURCE (CR 18.2a) — ASH_196 Gorian's
// unpreventable, SEC_050, LOF_108 read it. Ty's offer is suppressed per hit: it was asked once, above.
function _SWUJtl131Salvo(int $player, int $pow, string $srcTok, array $uids): void {
    global $playerID;
    if ($player <= 0 || $pow <= 0) return;
    foreach ($uids as $uid) {
        $playerID = $player;
        $mz = SWUFindMzByUID(intval($uid));
        if ($mz !== null) SWUDealDamageToUnit($mz, $pow, $player, _SWUDecodeDamageSource($srcTok), false, true);
    }
}

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_131:0"] = function($player, $mzID = '') {
// Turbolaser Salvo — "Choose an arena. A friendly space unit deals damage equal
                          // to its power to each enemy unit in that arena." Choose arena → choose the
                          // (space) dealer → AOE its power to each enemy in that arena.
            global $playerID;
            $playerID = intval($player);
            if (empty(ZoneSearch('mySpaceArena', AnyUnitFilter))) return; // no friendly space unit → fizzle
            DecisionQueueController::AddDecision($player, 'OPTIONCHOOSE', 'Ground&Space', 1, tooltip: "Choose_an_arena");
            DecisionQueueController::AddDecision($player, 'CUSTOM', 'JTL_131#0', 1);
            return;
};
