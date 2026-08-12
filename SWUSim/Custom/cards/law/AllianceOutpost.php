<?php

// LAW_019 Alliance Outpost — the Epic's cost is "[defeat a friendly token]". THE single source of truth
// for which tokens can pay it: the offer, the availability gate, and the burn-the-slot guard all call
// this, so they cannot disagree about what "payable" means.
// ⚠ KNOWN NARROW: only TOKEN UNITS in the arenas are collected today. Per the printed rules a friendly
// Shield, Experience, Credit or Force token is equally a friendly token and should be a legal cost.
// Those are not addressable by a plain arena mzID (Shield/Experience are attached upgrades needing a
// host+index pair; Credit and Force live outside the arenas entirely), so widening this needs a target
// -addressing scheme rather than a bigger ZoneSearch. Deferred deliberately — see law.md. When it is
// widened, THIS function is the only place that changes.
function _SWULaw019FriendlyTokens(int $player): array {
    global $playerID; $saved = $playerID; $playerID = $player;
    $tokens = [];
    foreach (["myGroundArena", "mySpaceArena"] as $z) {
        foreach (ZoneSearch($z, ["Token Unit"]) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $tokens[] = $mz;
        }
    }
    $playerID = $saved;
    return $tokens;
}

function _SWULaw019CanPayCost(int $player): bool { return !empty(_SWULaw019FriendlyTokens($player)); }

// LAW_019
// Alliance Outpost - [Vigilance] - HP 26
// Text: Epic Action [defeat a friendly token]: Give an Experience or Shield token to a unit, or create a Credit token.

// LAW_019 Alliance Outpost — defeat the chosen token (cost), then choose the reward mode.
$customDQHandlers["LAW_019#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) { SWUAfterAction(intval($player)); return; }
    SWUDefeatUnit(intval($player), $lastDecision);          // pay the [defeat a friendly token] cost
    DecisionQueueController::CleanupRemovedCards();
    DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", "@-&Experience&Shield&Credit", 1, "Give_an_Experience_or_Shield_token_to_a_unit,_or_create_a_Credit");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LAW_019#1", 1);
};

$customDQHandlers["LAW_019#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision === 'Credit') { SWUCreateCreditToken(intval($player), 1); SWUAfterAction(intval($player)); return; }
    $handler = ($lastDecision === 'Shield') ? 'GIVE_SHIELD' : 'GIVE_EXPERIENCE|1';
    $targets = [];
    foreach (["myGroundArena", "mySpaceArena", "theirGroundArena", "theirSpaceArena"] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $targets[] = $mz;
        }
    }
    if (empty($targets)) { SWUAfterAction(intval($player)); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Give_the_token_to_a_unit", $handler);
    SWUQueueAfterAction(intval($player));
};
