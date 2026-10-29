<?php
// IC27_101
// Cost 3 - General Hux - Fire Ventral Cannons! - [Aggression,Villainy] - Unit (Ground) 3/4 (unique)
//   Traits: First Order, Official
// Text: Overwhelm. Each other friendly First Order unit gets +1/+0 and gains Overwhelm.
//
// No registrations — his own Overwhelm is printed (auto-wired). The aura is read live from the board by
// _SWUIc27101Count, which feeds BOTH halves so they cannot disagree: ObjectCurrentPowerRaw adds the count as
// +N/+0, and HasConditionalKeyword_Overwhelm grants Overwhelm when it is > 0. Shape of _SWUIc27034Bonus:
//   • "Other" is by IDENTITY (UniqueID): Hux is First Order and does not buff himself, but a teammate's copy
//     is an "other friendly First Order unit", so two Huxes on one team buff each other.
//   • "friendly" = the unit's CONTROLLER plus, in Team Suns, that controller's teammates (user ruling
//     2026-08-25). SWUTeammatesOf is [] outside a team game.
//   • Counted: each Hux on the team adds its own +1. A blanked Hux (LostAbilities) grants nothing; a blanked
//     recipient still gets the +1 (a stat bonus is not one of ITS abilities) but its Overwhelm is suppressed.
//   • "First Order" is read with TraitContains (object-aware).
// Tests: SWUSim/Tests/Cases/ic27/GeneralHux_FireVentralCannons.md

function _SWUIc27101Count($obj): int {
    if ($obj === null || SWUObjGone($obj)) return 0;
    $ctrl = intval($obj->Controller ?? 0);
    if ($ctrl <= 0) return 0;
    $selfUID = intval($obj->UniqueID ?? -2);
    $n = 0;
    foreach (array_merge([$ctrl], SWUTeammatesOf($ctrl)) as $seat) {          // "friendly" = the team
        foreach (GetUnitsInPlay($seat) as $u) {
            if (!empty($u->removed) || ($u->CardID ?? '') !== 'IC27_101') continue;
            if (intval($u->UniqueID ?? -1) === $selfUID) continue;              // "other"
            if (LostAbilities($u)) continue;
            $n++;
        }
    }
    if ($n === 0 || !TraitContains($obj, 'First Order')) return 0;
    return $n;
}
