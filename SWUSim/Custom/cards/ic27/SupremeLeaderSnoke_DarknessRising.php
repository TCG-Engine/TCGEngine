<?php
// IC27_109
// Cost 8 - Supreme Leader Snoke - Darkness Rising - [Aggression,Villainy] - Unit (Ground) 8/6 (unique)
//   Traits: Force, First Order, Official
// Text: Overwhelm
//       When Played: Deal 4 damage to each other unit. Draw a card for each friendly unit defeated this way.
//
// PREVIEW card (no official rulings). Overwhelm is printed (generated keyword code).
//   • "Each other unit" = the whole table except Snoke — every seat, both arenas, tokens and leader units — the
//     LOF_170 Bendu pool: UID snapshot first, then each unit re-found by UID (a defeat compacts its arena under the
//     walk). Damage goes through SWUDealDamageToUnit with Snoke as the source (Bendu's shape), so Shields, immunity
//     and the "when dealt damage" observers all apply.
//   • The 4s are ONE ability dealing damage simultaneously (official Rancor Keeper ruling, 07/21/2026), so the loop
//     runs inside SWUSimulDefeatBegin/End (HMW_263 Wrecker): a "when a unit is defeated" observer that is itself a
//     victim still sees every co-victim (LOF_130 HK-47).
//   • "Friendly unit defeated this way" is measured, not assumed: a unit that was friendly when the 4 was dealt
//     (its controller or, in Team Suns, a teammate — SWUTeammatesOf is [] elsewhere) and is gone afterwards. A
//     unit saved by a Shield or by its HP is not counted.
//   • The draw is ONE instruction of N cards (DoDrawCard), so an empty deck is a single deck-out event of 3 x N
//     (user ruling 2026-09-07).
// Tests: SWUSim/Tests/Cases/ic27/SupremeLeaderSnoke_DarknessRising.md

$whenPlayedAbilities["IC27_109:0"] = function($player, $mzID) {
    global $playerID;
    $me = intval($player);
    $playerID = $me;
    $selfUID = SWUObjUID(GetZoneObject($mzID));
    $friendlySeats = array_merge([$me], SWUTeammatesOf($me));
    $victims = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o) || intval($o->UniqueID ?? -1) === $selfUID) continue;   // "other"
        $victims[] = ['uid' => intval($o->UniqueID ?? 0),
                      'friendly' => in_array(intval($o->Controller ?? 0), $friendlySeats, true)];
    }
    SWUSimulDefeatBegin();
    foreach ($victims as $v) {
        $playerID = $me;
        $vmz = SWUFindMzByUID($v['uid']);
        if ($vmz !== null) SWUDealDamageToUnit($vmz, 4, $me, $mzID);
    }
    SWUSimulDefeatEnd();
    $playerID = $me;
    $draws = 0;
    foreach ($victims as $v) {
        if ($v['friendly'] && SWUFindMzByUID($v['uid']) === null) $draws++;
    }
    if ($draws > 0) DoDrawCard($me, $draws);
    $playerID = $me;
};
