<?php
// HMW_200
// Cost 4 - Rish Loo, Traitorous Minister - [Cunning][Villainy] - Unit (Ground) 3/2
// Traits: Separatist, Gungan, Official - Unique
// Text: Hidden
//       When Played: Take control of an enemy non-leader unit with a Weakness token on it. At the start
//       of the next regroup phase, its owner takes control of it.
//
// HIDDEN needs no code (generator registry + generic keywords/Hidden.md coverage).
//
// The steal is MANDATORY ("take control", no "may"): a single legal target auto-resolves, none = clean
// fizzle. Target filter is three-way — ENEMY + NON-LEADER (deployed enemy leaders live in the arenas
// and must be excluded by the live object, not printed type) + carries a Weakness token (HMW_T02).
// The give-back mirrors JTL_235 Commandeer's shape — a PERM per-UID global consumed at RegroupPhaseStart
// — but returns CONTROL to the owner, not the card to hand (that regroup block lives beside JTL_235's
// in GameLogic). Control-wise everything else (non-pilot upgrade control transfer, arena crossing) rides
// SWUTakeControlOfUnit both ways.

$whenPlayedAbilities["HMW_200:0"] = function ($player, $mzID = '') {
    global $playerID;
    $playerID = intval($player);
    $targets = [];
    foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, NonLeaderUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if (SWUObjGone($o)) continue;
            if (IsLeaderUnit($o)) continue;   // live check — a pilot-made leader unit is still a leader
            $weak = false;
            foreach (GetUpgradesOnUnit($o) as $up) {
                if (($up->CardID ?? '') === 'HMW_T02') { $weak = true; break; }
            }
            if ($weak) $targets[] = $mz;
        }
    }
    if (empty($targets)) return;   // no weakened enemy — Rish still entered play, nothing more happens
    SWUQueueChooseTarget(intval($player), $targets, "Take_control_of_a_weakened_enemy_unit", "HMW_200#0");
};

$customDQHandlers["HMW_200#0"] = function ($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) return;
    $uid = intval($obj->UniqueID ?? 0);
    SWUTakeControlOfUnit(intval($player), $lastDecision);
    if ($uid > 0) AddGlobalEffects(intval($player), 'SWU_HMW200_RETURN_' . $uid);
};
