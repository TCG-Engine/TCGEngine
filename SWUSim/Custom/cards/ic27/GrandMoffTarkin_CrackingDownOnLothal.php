<?php
// IC27_023
// Cost 5 - Grand Moff Tarkin - Cracking Down on Lothal - [Vigilance,Villainy] - Unit (Ground) 3/6
//   Traits: Imperial, Official - unique
// Text: Each friendly unit with Sentinel gets +1/+1.
//
// No registrations — ObjectCurrentPower and ObjectCurrentHP (GameLogic.php) each add _SWUIc27023Bonus($obj) on every
// read, so the aura is recomputed from the live board (the IC27_034 Obi-Wan's Interceptor shape): it follows control
// changes, reaches units that gain or lose Sentinel later, and ends the moment Tarkin leaves play.
//   • "Each friendly unit" — both arenas, Tarkin himself included (no "other"), and in Team Suns the controller's
//     teammates (SWUTeammatesOf is [] outside a team game).
//   • Counted, not boolean: each friendly Tarkin is its own constant effect, so two on one team give +2/+2.
//   • "with Sentinel" is read live through HasKeyword_Sentinel — printed, granted and conditional Sentinel all count, and
//     a unit that LOST Sentinel (SOR_140) or all its abilities gets nothing.
//   • A blanked Tarkin (LostAbilities) grants nothing.
//   • Re-entry latch, PER UNIT: a conditional Sentinel can read power back — LOF_085 Praetorian Guard has Sentinel "while
//     you control a unit with 4 or more power", and its own power includes this bonus. While a unit's bonus is being
//     evaluated, a nested read of THAT unit's bonus returns 0, which breaks the loop. It must be per unit, not global:
//     a global latch would also drop the bonus of the OTHER unit Praetorian is reading, so a Sentinel 3-power unit made
//     4 by Tarkin would fail to give the Guard Sentinel (PraetorianGuard_BuffedSentinelEnablesIt).
// Tests: SWUSim/Tests/Cases/ic27/GrandMoffTarkin_CrackingDownOnLothal.md

function _SWUIc27023Bonus($obj): int {
    static $inProgress = [];
    if ($obj === null || SWUObjGone($obj)) return 0;
    $ctrl = intval($obj->Controller ?? 0);
    if ($ctrl <= 0) return 0;
    $n = 0;
    foreach (array_merge([$ctrl], SWUTeammatesOf($ctrl)) as $seat) {          // "friendly" = the team
        foreach (GetUnitsInPlay($seat) as $u) {
            if (!empty($u->removed) || ($u->CardID ?? '') !== 'IC27_023') continue;
            if (LostAbilities($u)) continue;
            $n++;
        }
    }
    if ($n === 0) return 0;
    $uid = intval($obj->UniqueID ?? 0);
    if (isset($inProgress[$uid])) return 0;
    $inProgress[$uid] = true;
    try {
        $hasSentinel = HasKeyword_Sentinel($obj);
    } finally {
        unset($inProgress[$uid]);
    }
    return $hasSentinel ? $n : 0;
}
