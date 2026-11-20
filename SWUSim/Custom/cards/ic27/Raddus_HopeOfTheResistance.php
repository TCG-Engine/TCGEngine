<?php
// IC27_043
// Cost 9 - Raddus - Hope of the Resistance - [Vigilance,Heroism] - Unit (Space) 8/9
// Traits: Resistance, Vehicle, Capital Ship - Unique
// Text: Restore 3. While this unit is exhausted, cards your opponents play cost [3 resources] more.
//
// The surcharge is a $playCostFieldModifiers row registered in GameLogic.php beside SOR_034 Del Meeko (that array is
// initialised after cards/_loader.php, so a registration here would be wiped). It reaches every path that prices a
// play through _SWUPlayCostModifierDelta — a normal play, Smuggle, the affordability gates. The PILOTING path prices
// itself (SWUComputePilotCost) and never ran the field modifiers, so it adds _SWUIc27043Tax explicitly: playing a
// Pilot as an upgrade is still playing a card. "Your opponents" is team-aware (SWUIsEnemySeat): every other seat in
// Twin Suns, the other team in Team Suns, never Raddus's own controller. A blanked Raddus is skipped by the
// field-modifier loop and by _SWUIc27043Tax alike. Restore 3 is the generated keyword.
// Tests: SWUSim/Tests/Cases/ic27/Raddus_HopeOfTheResistance.md

// The surcharge ONE Raddus ($sourceObj) puts on a card $subjectPlayer plays.
function _SWUIc27043TaxFrom($sourceObj, int $subjectPlayer): int {
    if ($sourceObj === null || !empty($sourceObj->removed)) return 0;
    if (intval($sourceObj->Status ?? 1) !== 0) return 0;                       // "while this unit is exhausted"
    if (!SWUIsEnemySeat(intval($sourceObj->Controller ?? 0), $subjectPlayer)) return 0;
    return 3;
}

// Every exhausted Raddus on the table, for a play path that does not run the field-modifier loop (Piloting).
function _SWUIc27043Tax(int $subjectPlayer): int {
    $tax = 0;
    foreach (GetLiveSeatsArray() as $seat) {
        foreach (GetField($seat) as $fo) {
            if (($fo->CardID ?? '') !== 'IC27_043' || SWUObjGone($fo) || HasNoAbilities($fo) || LostAbilities($fo)) continue;
            $tax += _SWUIc27043TaxFrom($fo, $subjectPlayer);
        }
    }
    return $tax;
}
