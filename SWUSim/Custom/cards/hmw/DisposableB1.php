<?php
// HMW_103
// Cost 1 - Disposable B1 - [Command,Villainy] - Power 2 - HP 1 - Separatist, Droid, Trooper
// Text: When Played: If another friendly unit entered play this phase (including leader and token units), draw a card.
//   (the preview text's "leader and token units" is a typo for "leader and token units")

// HMW_103 Disposable B1 — When Played: if ANOTHER FRIENDLY unit ENTERED PLAY this phase, draw a card.
//
// Three words in that sentence each pick a different helper, and getting any of them wrong is silent:
//
//   "ENTERED PLAY"  -> SWUUnitEnteredPlayThisPhase (SWU_ENTERED_PHASE_), NOT SWUUnitPlayedThisPhase.
//                      The printed parenthetical "(including leader and token units)" IS this
//                      distinction: a deployed leader and a created token both ENTER play without
//                      being PLAYED, so SWU_PLAYED_UNIT_ — which only ActivateCard sets — never sees
//                      them. Pinned by TokenUnitCounts and DeployedLeaderCounts, which are exactly the
//                      two cases where the flags disagree.
//   "FRIENDLY"      -> SWUFriendlyUnitObjects, which spans the TEAM in Team Suns. "You control" would
//                      be GetUnitsInPlay; a teammate's unit is friendly but you do not control it.
//   "ANOTHER"       -> exclude THIS unit by UniqueID. CollectEntryTriggers stamps SWU_ENTERED_PHASE_ on
//                      the entering unit BEFORE it dispatches the When Played, so B1's own flag is
//                      already set when this runs — without the self-exclusion the condition would be
//                      true every single time it is played. Pinned by NoOtherUnitEnteredThisPhase_NoDraw.
$whenPlayedAbilities["HMW_103:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self    = GetZoneObject($mzID);
    $selfUID = intval($self->UniqueID ?? -1);
    foreach (SWUFriendlyUnitObjects(intval($player)) as $u) {
        if (SWUObjGone($u)) continue;
        if (intval($u->UniqueID ?? -2) === $selfUID) continue;      // "ANOTHER"
        if (!SWUUnitEnteredPlayThisPhase($u)) continue;
        DoDrawCard(intval($player), 1);
        return;                                                     // one card, however many qualify
    }
};
