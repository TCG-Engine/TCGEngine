<?php
// LAW_002
// Cost 5 - Tobias Beckett - People are Predictable - [Cunning,Vigilance] - Power 4 - HP 6
// Text: Action [Exhaust]: Choose a friendly unit. An opponent takes control of it. If they do, create a Credit token.
// DeployText: When Deployed: Defeat any number of units you own but don't control. For each unit defeated this way, create a Credit token and draw a card.
// Epic Action: If you control 5 or more resources, deploy this leader.

// ── LAW_002 Tobias Beckett ────────────────────────────────────────────────────
// Front Action [Exhaust]: choose a friendly unit; an opponent takes control of it; if they do, create
// a Credit token. Deployed When Deployed: defeat any number of units you OWN but don't control; for
// each, create a Credit token and draw a card.
$leaderAbilities["LAW_002"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, NonLeaderUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $targets[] = $mz;
        }
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Give_a_friendly_unit_to_an_opponent_(then_create_a_Credit)", "LAW_002#0");
    SWUQueueAfterAction($player);
};

$customDQHandlers["LAW_002#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $newMz = SWUTakeControlOfUnit(OtherPlayer(intval($player)), $lastDecision);   // opponent takes control
    // "If they do, create a Credit token." — only when control ACTUALLY transferred. LAW_149 Rey
    // ("opponents can't take control of this unit") blocks the transfer ($newMz === '') → no Credit.
    if ($newMz !== '') SWUCreateCreditToken(intval($player), 1);
};

$whenPlayedAbilities["LAW_002:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // units you OWN but don't control = your-owned units sitting in the opponent's arenas
    $targets = [];
    foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Owner ?? 0) === intval($player)) $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    $max = count($targets);
    DecisionQueueController::AddDecision($player, "MZMULTICHOOSE", "0|{$max}|" . implode('&', $targets), 1, tooltip: "Defeat_any_number_of_units_you_own_but_don't_control");
    DecisionQueueController::AddDecision($player, "CUSTOM", "LAW_002#1", 1);
};

$customDQHandlers["LAW_002#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $uids = [];
    foreach (explode('&', $lastDecision) as $mz) {
        if ($mz === '' || $mz === '-' || $mz === 'PASS') continue;
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID ?? 0);
    }
    foreach ($uids as $uid) {
        if ($uid <= 0) continue;
        $mz = SWUFindMzByUID($uid);
        if ($mz === null) continue;
        SWUDefeatUnit(intval($player), $mz);
        SWUCreateCreditToken(intval($player), 1);
        DoDrawCard(intval($player), 1);
    }
};
