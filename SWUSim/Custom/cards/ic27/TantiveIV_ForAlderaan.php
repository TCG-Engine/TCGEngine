<?php
// IC27_160
// Cost 5 - Tantive IV - For Alderaan - [Cunning,Heroism] - Unit (Space) 3/5 (unique)
//   Traits: Rebel, Vehicle, Capital Ship
// Text: Each other friendly Rebel unit gains Raid 2 and Saboteur.
//
// PREVIEW card (no official rulings). No registrations — the aura is read live from the board by
// _SWUIc27160Count, which feeds BOTH keyword readers in KeywordEffects.php so the halves cannot disagree:
// GetConditionalKeyword_Raid_Value adds 2 per counted Tantive, HasConditionalKeyword_Saboteur grants on > 0.
// Shape of IC27_101 General Hux (_SWUIc27101Count):
//   • "Other" is by IDENTITY (UniqueID): Tantive is a Rebel and does not cover itself, but a teammate's copy is
//     an "other friendly Rebel unit", so two Tantives on one team cover each other.
//   • "friendly" = the unit's CONTROLLER plus, in Team Suns, that controller's teammates (user ruling
//     2026-08-25). SWUTeammatesOf is [] outside a team game, so a Twin Suns far seat is not friendly.
//   • Counted: each Tantive on the team is its own source, and Raid values add (CR 7.5.8.b — the LAW_233 Galen
//     Erso ruling), so two give Raid 4. Saboteur does not stack. A blanked Tantive (LostAbilities) grants
//     nothing; a blanked recipient loses both through SWUKeywordSuppressed in the generated readers.
//   • "Rebel" is read with TraitContains (object-aware), so a deployed Rebel leader unit counts.
// Tests: SWUSim/Tests/Cases/ic27/TantiveIV_ForAlderaan.md

function _SWUIc27160Count($obj): int {
    if ($obj === null || SWUObjGone($obj)) return 0;
    $ctrl = intval($obj->Controller ?? 0);
    if ($ctrl <= 0) return 0;
    $selfUID = intval($obj->UniqueID ?? -2);
    $n = 0;
    foreach (array_merge([$ctrl], SWUTeammatesOf($ctrl)) as $seat) {          // "friendly" = the team
        foreach (GetUnitsInPlay($seat) as $u) {
            if (!empty($u->removed) || ($u->CardID ?? '') !== 'IC27_160') continue;
            if (intval($u->UniqueID ?? -1) === $selfUID) continue;              // "other"
            if (LostAbilities($u)) continue;
            $n++;
        }
    }
    if ($n === 0 || !TraitContains($obj, 'Rebel')) return 0;
    return $n;
}
