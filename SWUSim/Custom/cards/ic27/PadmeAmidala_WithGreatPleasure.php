<?php
// IC27_083
// Cost 8 - Padmé Amidala - With Great Pleasure - [Command,Heroism] - Unit (Ground) 5/7
// Traits: Naboo, Republic, Official - Unique
// Text: Sentinel. Other friendly [Heroism] units get +3/+3.
//
// No registrations — ObjectCurrentPower and ObjectCurrentHP (GameLogic.php) add _SWUIc27083Bonus($obj) on every read,
// so the aura is recomputed from the live board (IC27_034 Obi-Wan's Interceptor's shape):
//   • "Heroism" is an ASPECT ICON, read with SWUCardAspectIcons (a token with the icon — TWI_T02 Clone Trooper — counts).
//   • "Other" by UniqueID: Padmé is Heroism herself and does not buff herself, but a teammate's Padmé is another
//     friendly Heroism unit, so two on one team buff each other.
//   • "Friendly" = the unit's controller plus, in Team Suns, that controller's teammates.
//   • Counted per Padmé; a blanked Padmé grants nothing. Sentinel is the generated keyword.
// Tests: SWUSim/Tests/Cases/ic27/PadmeAmidala_WithGreatPleasure.md

function _SWUIc27083Bonus($obj): int {
    if ($obj === null || SWUObjGone($obj)) return 0;
    if (!in_array('Heroism', SWUCardAspectIcons((string)($obj->CardID ?? '')), true)) return 0;
    $ctrl = intval($obj->Controller ?? 0);
    if ($ctrl <= 0) return 0;
    $selfUID = intval($obj->UniqueID ?? -2);
    $n = 0;
    foreach (array_merge([$ctrl], SWUTeammatesOf($ctrl)) as $seat) {
        foreach (GetUnitsInPlay($seat) as $u) {
            if (!empty($u->removed) || ($u->CardID ?? '') !== 'IC27_083') continue;
            if (intval($u->UniqueID ?? -1) === $selfUID) continue;
            if (LostAbilities($u)) continue;
            $n++;
        }
    }
    return 2 * $n;
}
