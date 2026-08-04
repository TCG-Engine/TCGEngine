<?php
// IC27_024
// Cost 6 - Grand Admiral Thrawn - Listen to Me Carefully - [Vigilance,Villainy] - Unit (Ground) 4/4 (unique)
//   Traits: Imperial, Official
// Text: When Played / On Attack / When Defeated: You may give an Experience token to a friendly unit.
//       It gains Sentinel for this phase.
//
// ONE ability registered on THREE windows — the same closure in all three registries.
// ⚠ The stub generator originally detected only the WhenDefeated window: it recognised the TIGHT
// slash form ("When Played/") but not the SPACED header this card uses ("When Played / On Attack /
// When Defeated:"), so the other two halves dispatched to nothing. Fixed in zzCardCodeGenerator.php
// (both slash matches now tolerate surrounding whitespace); the local GeneratedAbilityStubs.php —
// which is gitignored — was hand-patched to match until the next regen.
//
// "a friendly unit" carries no "another", so Thrawn is a legal target for his own ability (On Attack /
// When Played). MZMAYCHOOSE is the right shape for a "may" single-target and is safe inside the On
// Attack window (unlike a mandatory MZCHOOSE, which is silently skipped there).
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

// When Played / On Attack: Thrawn is still in play and "a friendly unit" has no "another" qualifier,
// so he may target himself.
$ic27024Live = function($player, $mzID = '') use ($ic27024Offer) {
    $ic27024Offer($player, $mzID, -1);
};
$whenPlayedAbilities["IC27_024:0"] = $ic27024Live;
$onAttackAbilities["IC27_024:0"]   = $ic27024Live;

// When Defeated: the collection runs BEFORE CleanupRemovedCards, so Thrawn is still sitting in the
// arena array and would otherwise be offered as a recipient for his own token — a unit on its way out
// of play cannot receive one. Exclude the source explicitly rather than relying on a `removed` flag
// that is not yet set at this point.
$whenDefeatedAbilities["IC27_024:0"] = function($player, $mzID = '') use ($ic27024Offer) {
    global $playerID; $playerID = intval($player);
    $self = ($mzID !== '') ? GetZoneObject($mzID) : null;
    $selfUID = ($self !== null) ? intval($self->UniqueID ?? -1) : -1;
    $ic27024Offer($player, $mzID, $selfUID);
};

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
