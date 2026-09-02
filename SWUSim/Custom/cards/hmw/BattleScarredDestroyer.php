<?php
// HMW_158
// Cost 6 - Battle-Scarred Destroyer - [Aggression][Villainy] - Unit, Space - Power 7 - HP 8
// Traits: Imperial, Vehicle, Capital Ship - non-unique
// Text: When Played: Deal 4 damage to a friendly unit.
//
// MANDATORY — no "may" and no "up to" — so a plain MZCHOOSE (SWUQueueChooseTarget via the shared offer
// helper), never a MAY-choose. The player is committed once the unit is played.
//
// ⚠ "A FRIENDLY UNIT" INCLUDES THIS UNIT. A unit's When Played resolves after it has entered play, so
// the Destroyer is itself a legal target — and on an otherwise empty board it is the ONLY one, so the
// mandatory choose auto-resolves onto it and it damages itself for 4. It is a 7/8, so that is
// self-limiting rather than self-defeating. Pinned by AloneOnTheBoard_ItMustDamageITSELF.
// The text says "a friendly unit", not "another", so there is no self-exclusion to apply.
//
// ⚠ "FRIENDLY" IS TEAM-WIDE, WHICH IS WHY THIS USES THE SHARED HELPER. `side => 'friendly'` maps to
// 'team' in _SWUCollectUnitTargets, so in Team Suns a TEAMMATE's unit is a legal target even though you
// do not control it, and outside a team game 'team' degrades to your own board (byte-identical to
// Premier). The older in-tree precedent for this exact clause — JTL_219 Rafa Martez — hand-rolls
// `ZoneSearch('myGroundArena') + ZoneSearch('mySpaceArena')`, which is SELF-ONLY and cannot reach a
// teammate; do not copy that shape. Pinned by TeamSuns_ATeammatesUnitIsFriendly.
//
// No arena restriction in the text, so a friendly GROUND unit is as legal as a space one even though
// the Destroyer is a Capital Ship (guarded by the offer section).

$whenPlayedAbilities["HMW_158:0"] = function ($player, $mzID = '') {
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'DEAL_UNIT_DAMAGE',
        'amount'       => 4,
        'side'         => 'friendly',
        'prompt'       => 'Deal_4_damage_to_a_friendly_unit',
    ]);
};
