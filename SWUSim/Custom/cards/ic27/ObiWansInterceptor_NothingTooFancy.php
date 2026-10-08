<?php
// IC27_034
// Cost 2 - Obi-Wan's Interceptor - Nothing Too Fancy - [Vigilance,Heroism] - Unit (Space) 2/3
// Traits: Jedi, Republic, Vehicle, Fighter - unique
// Text: Other friendly Republic units get +0/+1.
//
// No registrations — ObjectCurrentHP (GameLogic.php) adds _SWUIc27034Bonus($obj) on every HP read, so the
// aura is recomputed from the live board: it covers units that enter later, follows control changes, and
// ends the moment the Interceptor leaves play (the state-based sweep then defeats any ally it was holding
// up). +0/+1 is HP only — there is no power half.
//   • "Other" is by IDENTITY (UniqueID), never by CardID: the Interceptor is itself Republic and does not
//     buff itself, but a teammate's copy is an "other friendly Republic unit" and the two buff each other.
//   • "friendly" = the unit's CONTROLLER plus, in Team Suns, that controller's teammates (user ruling
//     2026-08-25, IBH_095; the HMW_006 Omega aura shape). SWUTeammatesOf is [] outside a team game, so
//     Premier and Twin Suns read the controller only. This is why it does NOT use SWUTraitCommanderBonus,
//     which reads the controller's own board only.
//   • Counted, not boolean: each Interceptor on the team adds its own +1.
//   • "Republic" is read with TraitContains (object-aware: per-instance trait loss/grants apply). It is an
//     exact trait — "New Republic" (SEC_044, SOR_191) is a different trait and gets nothing.
//   • A blanked Interceptor (LostAbilities) grants nothing; a blanked RECIPIENT still receives it.
//   • The `removed` filter is not load-bearing for the END of the aura (mutation-green, 2026-10-07): both
//     defeat paths compact the dead Interceptor out of the arena BEFORE the no-remaining-HP sweep reads HP
//     (_SWUSweepAfterDefeat / combat's post-step-3 SWUCheckShrinkDefeats). It stays for HP reads made in
//     the window between removal and compaction, like every sibling aura helper.
// Tests: SWUSim/Tests/Cases/ic27/ObiWansInterceptor_NothingTooFancy.md

function _SWUIc27034Bonus($obj): int {
    if ($obj === null || SWUObjGone($obj)) return 0;
    $ctrl = intval($obj->Controller ?? 0);
    if ($ctrl <= 0) return 0;
    $selfUID = intval($obj->UniqueID ?? -2);
    $n = 0;
    foreach (array_merge([$ctrl], SWUTeammatesOf($ctrl)) as $seat) {          // "friendly" = the team
        foreach (GetUnitsInPlay($seat) as $u) {
            if (!empty($u->removed) || ($u->CardID ?? '') !== 'IC27_034') continue;
            if (intval($u->UniqueID ?? -1) === $selfUID) continue;              // "Other"
            if (LostAbilities($u)) continue;
            $n++;
        }
    }
    if ($n === 0 || !TraitContains($obj, 'Republic')) return 0;
    return $n;
}
