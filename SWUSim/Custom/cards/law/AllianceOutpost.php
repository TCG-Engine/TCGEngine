<?php

// LAW_019 Alliance Outpost — the Epic's cost is "[defeat a friendly token]". THE single source of truth
// for which tokens can pay it: the offer, the availability gate, and the burn-the-slot guard all call
// this, so they cannot disagree about what "payable" means.
// Every friendly token is a legal cost (widened 2026-09-17, game 505707 — the Chewbacca deck's line is
// "play a Shielded unit, defeat its Shield with the Epic, take the Credit"):
//   • token UNITS in $player's arenas                          → "myGroundArena-N"
//   • token UPGRADES (Shield, Experience, Advantage …) attached to a unit or base $player controls
//     (CR 3.50.f: a player controls the token upgrades on units they control — whoever created them)
//                                                              → subcard mzID "myGroundArena-N.uK"
//   • Credit tokens in $player's resource zone (CR 3.13)       → "myResources-N"
//   • the Force token (CR 3.11; stored as player state, shown on the base) → "myBase-0"
// Non-token upgrades and every enemy token are excluded.
function _SWULaw019FriendlyTokens(int $player): array {
    global $playerID; $saved = $playerID; $playerID = $player;
    $tokens = [];
    foreach (["myGroundArena", "mySpaceArena"] as $z) {
        foreach (ZoneSearch($z, ["Token Unit"]) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $tokens[] = $mz;
        }
    }
    foreach (SWUGetUpgradeSubcardMzIDs('') as $subMz) {
        if (strpos($subMz, 'my') !== 0) continue;                       // host controlled by $player
        $sub = MZParseSubcardID($subMz);
        if ($sub === null) continue;
        $host = GetZoneObject($sub['host']);
        $up = $host->Subcards[$sub['subIndex']] ?? null;
        $cid = is_array($up) ? ($up['CardID'] ?? '') : ($up->CardID ?? '');
        if ($cid !== '' && strpos(strtolower(CardType($cid) ?? ''), 'token') !== false) $tokens[] = $subMz;
    }
    $resources = GetResources($player);
    for ($i = 0; $i < count($resources); $i++) {
        if (empty($resources[$i]->removed) && SWUIsCreditToken($resources[$i]->CardID ?? '')) $tokens[] = "myResources-{$i}";
    }
    if (PlayerHasTheForce($player)) $tokens[] = "myBase-0";
    $playerID = $saved;
    return $tokens;
}

function _SWULaw019CanPayCost(int $player): bool { return !empty(_SWULaw019FriendlyTokens($player)); }

// Defeat the chosen friendly token. The answer's shape says which kind it is (see the list above).
function _SWULaw019DefeatToken(int $player, string $mz): void {
    if (MZParseSubcardID($mz) !== null)        { SWUDefeatUpgradeByMzID($player, $mz); return; }
    if (strpos($mz, 'myResources-') === 0)     { SWUDefeatCreditToken($mz); return; }
    if ($mz === 'myBase-0')                    { SWUDefeatForceToken($player); return; }
    SWUDefeatUnit($player, $mz);
}

// LAW_019
// Alliance Outpost - [Vigilance] - HP 26
// Text: Epic Action [defeat a friendly token]: Give an Experience or Shield token to a unit, or create a Credit token.

// LAW_019 Alliance Outpost — defeat the chosen token (cost), then choose the reward mode.
$customDQHandlers["LAW_019#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) { SWUAfterAction(intval($player)); return; }
    _SWULaw019DefeatToken(intval($player), strval($lastDecision));  // pay the [defeat a friendly token] cost
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
