<?php
// IC27_024
// Cost 6 - Grand Admiral Thrawn - Listen to Me Carefully - [Vigilance,Villainy] - Unit (Ground) 4/4 (unique)
//   Traits: Imperial, Official
// Text: When Played/On Attack/When Defeated: You may give an Experience token to another friendly unit.
//       It gains Sentinel for this phase.
//
// ONE ability registered on THREE windows — the same closure in all three registries.
// ⚠ The stub generator originally detected only the WhenDefeated window: it recognised the TIGHT
// slash form ("When Played/") but not the SPACED header this card uses ("When Played / On Attack /
// When Defeated:"), so the other two halves dispatched to nothing. Fixed in zzCardCodeGenerator.php
// (both slash matches now tolerate surrounding whitespace); the local GeneratedAbilityStubs.php —
// which is gitignored — was hand-patched to match until the next regen.
//
// "ANOTHER friendly unit" (official text, 2026-11-07 — the preview mock read "a friendly unit"), so Thrawn
// is never a target for his own ability, in any of the three windows. MZMAYCHOOSE is the right shape for
// a "may" single-target and is safe inside the On Attack window (unlike a mandatory MZCHOOSE, which is
// silently skipped there).
$ic27024Offer = function($player, $mzID, int $excludeUID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if (SWUObjGone($o)) continue;
            if ($excludeUID >= 0 && intval($o->UniqueID ?? 0) === $excludeUID) continue;
            $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Give_an_Experience_token_to_a_friendly_unit?",
        "Give_an_Experience_token_(it_gains_Sentinel_this_phase)", "IC27_024#0");
};

// "Another" excludes Thrawn by UID. The positional mzID can be STALE by When Defeated dispatch (after a
// combat death he has been cleaned up and a survivor shifted into his slot), so only exclude the slot
// when it really is Thrawn — excluding whatever sits there excluded the SURVIVOR (same idiom as SEC_202
// Rebel Propagandist). In the When Played / On Attack windows the slot is always Thrawn himself.
$ic27024Ability = function($player, $mzID = '') use ($ic27024Offer) {
    global $playerID; $playerID = intval($player);
    $self = ($mzID !== '') ? GetZoneObject($mzID) : null;
    $selfUID = ($self !== null && ($self->CardID ?? '') === 'IC27_024') ? intval($self->UniqueID ?? -1) : -1;
    $ic27024Offer($player, $mzID, $selfUID);
};
$whenPlayedAbilities["IC27_024:0"]   = $ic27024Ability;
$onAttackAbilities["IC27_024:0"]     = $ic27024Ability;
$whenDefeatedAbilities["IC27_024:0"] = $ic27024Ability;

$customDQHandlers["IC27_024#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    // Two different lifetimes: the Experience token is a permanent upgrade, the Sentinel grant is a
    // phase-duration registry token ('SENTINEL') swept centrally by SWUExpireTurnEffects.
    DoGiveExperienceToken(intval($player), $lastDecision);
    AddTurnEffect($lastDecision, 'SENTINEL');
};
