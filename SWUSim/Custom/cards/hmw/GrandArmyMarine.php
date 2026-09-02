<?php
// HMW_072
// Cost 2 - Grand Army Marine - [Vigilance][Heroism] - Unit, Ground - Power 2 - HP 2
// Traits: Gungan, Trooper - non-unique
// Text: When Played: Give a Shield token to a friendly Gungan unit.
//
// MANDATORY (no "may", no "up to") -> a plain MZCHOOSE via the shared offer helper.
//
// ⚠ THE MARINE IS ITSELF A GUNGAN, and a unit's When Played resolves AFTER it has entered play — so it
// is always in its own target pool and this clause can never fizzle. The text says "a friendly Gungan
// unit", not "another", so there is no self-exclusion. Played alone it therefore shields ITSELF via the
// single-target auto-resolve (AloneOnTheBoard_ShieldsITSELF); with only a non-Gungan friendly beside it
// the pool narrows back to itself (NonGunganFriendlyOnly_StillShieldsITSELF).
//
// ⚠ DELIBERATELY NOT `GiveTokenUpgrade(..., 'friendlyOnly' => true)`, which is the obvious shorthand for
// this card. That option maps to `side => 'my'` — SELF-ONLY — so in Team Suns it could never reach a
// TEAMMATE's Gungan, even though "friendly" spans the team. Calling SWUOfferUnitTarget directly lets the
// pool be `side => 'friendly'` ('team', degrading to your own board outside a team game, so Premier is
// byte-identical). Same trap as the older hand-rolled ZoneSearch pools (JTL_219 Rafa Martez).
// Pinned by TeamSuns_ATeammatesGunganIsFriendly.
//
// Two restrictions, both in the OFFER rather than at resolution: "friendly" (controller) and "Gungan"
// (trait, matched object-aware via TraitContains so a GRANTED Gungan trait would count too).

$whenPlayedAbilities["HMW_072:0"] = function ($player, $mzID = '') {
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'GIVE_SHIELD',
        'side'         => 'friendly',
        'traits'       => ['Gungan'],
        'prompt'       => 'Give_a_Shield_token_to_a_friendly_Gungan_unit',
    ]);
};
