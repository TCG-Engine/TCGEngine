<?php
// HMW_243
// Cost 2 - Sun Fac - Poggle's Second - [Villainy] - Unit (Ground) 2/3 - Trait: Separatist - Unique
// Text: When Played: Give a unit Grit for this phase. (It gets +1/+0 for each damage on it.)
//
// A word-for-word mirror of SEC_255 Remote Escort Tank ("When Played: Give a unit Sentinel for this
// phase"), down to the unqualified target.
//
// "A unit" names no controller, no arena and no card type, so the pool is the WHOLE TABLE: friendly and
// enemy alike, either arena, deployed leader units included, and Sun Fac HIMSELF — he is already in play
// by the time his own When Played resolves. SWUOfferUnitTarget's default side 'any' is exactly that set,
// and it routes through SWUAllUnits, so at three or four seats it fans out across every live opponent
// rather than stopping at the one opponent a two-player board makes visible.
//
// MANDATORY, not "may": the printed text carries no "you may" and no "up to", so this is
// SWUQueueChooseTarget. (The hidden-zone rule that makes every play-from-HAND offer declinable does not
// reach here — nothing is revealed by pointing at a unit already on the board.)
//
// The grant itself is pure registry data: 'GRIT' is a GRANT_KEYWORD row whose default duration is the
// phase, and HasKeyword_Grit consults SWUHasTurnEffectKeyword, so ObjectCurrentPower's
// "+1/+0 for each damage" branch picks it up and RECOMPUTES it on every read — damage taken after the
// grant counts too. The ^HMW_243 suffix is provenance only (it names the source card in the Active
// Effects popup); SWUExpireTurnEffects strips the token at the next regroup.
$whenPlayedAbilities["HMW_243:0"] = function($player, $mzID = '') {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'GRANT_PHASE_KEYWORD|GRIT^HMW_243',
        'prompt'       => "Give_a_unit_Grit_for_this_phase",
    ]);
};
