<?php
// LOF_006
// Cost 6 - Supreme Leader Snoke - In the Seat of Power - [Command,Villainy] - Power 4 - HP 7
// Text: Action [1 resource, Exhaust]: Give an Experience token to the unit with the most power among friendly Villainy units. (If multiple units are tied, choose one.)
// DeployText: On Attack: Give an Experience token to the unit with the most power among friendly Villainy units.
// Epic Action: If you control 6 or more resources, deploy this leader.

// LOF_006 Supreme Leader Snoke — On Attack: Give an Experience token to the unit with the most power among
// friendly Villainy units. On a TIE for most power the PLAYER chooses (mirrors the front side). In OnAttack a
// mandatory MZCHOOSE is skipped, so the tie uses MZMAYCHOOSE; combat owns the After Action (no SWUAfterAction).
$onAttackAbilities["LOF_006:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $villainy = [];
    foreach (array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter)) as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (strpos(CardAspect($o->CardID ?? '') ?? '', 'Villainy') !== false) $villainy[] = $mz;
    }
    if (empty($villainy)) return;
    $maxP = -1;
    foreach ($villainy as $mz) { $p = intval(ObjectCurrentPower(GetZoneObject($mz))); if ($p > $maxP) $maxP = $p; }
    $top = array_values(array_filter($villainy, fn($mz) => intval(ObjectCurrentPower(GetZoneObject($mz))) === $maxP));
    if (count($top) === 1) { DoGiveExperienceToken(intval($player), $top[0]); return; }
    SWUQueueMayChooseTarget(intval($player), $top, "Choose_a_tied_Villainy_unit_for_an_Experience_token",
        "Choose_a_tied_Villainy_unit", "LOF_006#1");
};

// LOF_006 Supreme Leader Snoke — Action [1 resource, Exhaust]: Give an Experience token to the unit with
// the most power among friendly Villainy units. (Choose one if tied.)
$leaderAbilities["LOF_006"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; }
    $villainy = [];
    foreach (array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter)) as $mz) {
        $o = GetZoneObject($mz); if (SWUObjGone($o)) continue;
        if (strpos(CardAspect($o->CardID ?? '') ?? '', 'Villainy') !== false) $villainy[] = $mz;
    }
    if (empty($villainy)) { SWUAfterAction($player); return; }
    $maxP = -1;
    foreach ($villainy as $mz) { $p = intval(ObjectCurrentPower(GetZoneObject($mz))); if ($p > $maxP) $maxP = $p; }
    $top = array_values(array_filter($villainy, fn($mz) => intval(ObjectCurrentPower(GetZoneObject($mz))) === $maxP));
    if (count($top) === 1) {
        DoGiveExperienceToken($player, $top[0]);
        SWUAfterAction($player);
        return;
    }
    SWUQueueChooseTarget($player, $top, "Choose_a_tied_Villainy_unit_for_an_Experience_token", "LOF_006#0");
};

$customDQHandlers["LOF_006#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') DoGiveExperienceToken(intval($player), $lastDecision);
    SWUAfterAction(intval($player));
};

// ══════════════════════════════════════════════════════════════════════════════════════════════════
// LOF leaders — DEPLOYED-unit On Attack abilities (the leader-side Actions are in LeaderAbilities.php).
// Per the OnAttack $playerID-restore gotcha, multi-target picks use MZMAYCHOOSE (SWUQueueMayChooseTarget)
// or a CUSTOM continuation; combat owns the after-action (no SWUAfterAction here).
// ══════════════════════════════════════════════════════════════════════════════════════════════════
$customDQHandlers["LOF_006#1"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $playerID = intval($player);
  if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS')
    DoGiveExperienceToken(intval($player), $lastDecision);
};
