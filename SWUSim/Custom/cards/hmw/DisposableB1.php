<?php
// HMW_103
// Cost 1 - Disposable B1 - [Command,Villainy] - Power 2 - HP 1 - Separatist, Droid, Trooper
// Text: When Played: If another friendly unit entered play this phase (including leader and token units), draw a card.
//   (the preview text's "leader and token units" is a typo for "leader and token units")

// HMW_103 Disposable B1 — When Played: if ANOTHER FRIENDLY unit ENTERED PLAY this phase, draw a card.
//
// Three words in that sentence each pick a different helper, and getting any of them wrong is silent:
//
//   "ENTERED PLAY"  -> the SWU_ENTERED_PHASE_ flags, NOT SWU_PLAYED_UNIT_ (SWUUnitPlayedThisPhase).
//                      The printed parenthetical "(including leader and token units)" IS this
//                      distinction: a deployed leader and a created token both ENTER play without
//                      being PLAYED, so SWU_PLAYED_UNIT_ — which only ActivateCard sets — never sees
//                      them. Pinned by TokenUnitCounts and DeployedLeaderCounts, which are exactly the
//                      two cases where the flags disagree.
//   "FRIENDLY"      -> this seat's entries plus each Team Suns TEAMMATE's. The flag is stamped on the
//                      unit's controller at entry, so a teammate's entrant lives on the teammate's seat.
//   "ANOTHER"       -> exclude THIS unit by UniqueID. CollectEntryTriggers stamps SWU_ENTERED_PHASE_ on
//                      the entering unit BEFORE it dispatches the When Played, so B1's own flag is
//                      already set when this runs — without the self-exclusion the condition would be
//                      true every single time it is played. Pinned by NoOtherUnitEnteredThisPhase_NoDraw.
//
// ⚠ TALLY THE FLAGS, DON'T SCAN THE BOARD (bug report game 1647080). "Entered play this phase" is a fact
// about the past: a TIE Fighter played and then defeated before B1 still entered play. Asking each unit
// IN PLAY whether it entered could never see it, so B1 drew nothing. _SWUEnteredThisPhaseCount is the
// shared tally (also the TS26_02/TS26_04 gate). Pinned by EntrantDefeatedBeforeB1_StillCounts.
$whenPlayedAbilities["HMW_103:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    if (_SWUEnteredThisPhaseCount(intval($player), intval($self->UniqueID ?? -1)) > 0) {
        DoDrawCard(intval($player), 1);                             // one card, however many qualify
    }
};
