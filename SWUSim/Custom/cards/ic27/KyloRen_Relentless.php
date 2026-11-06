<?php
// IC27_106
// Cost 5 - Kylo Ren - Relentless - [Aggression,Villainy] - Unit (Ground) 5/5 (unique)
//   Traits: Force, First Order
// Text: Hidden
//       On Attack: If this is the first time this unit has attacked this phase, ready him.
//
// PREVIEW card (no official rulings). Hidden is printed (generated keyword code).
//   • "first time this unit has attacked this phase" counts SWU_UNIT_ATTACKED_<uid>. Unlike the idempotent
//     SWU_ATTACKED_<uid> family, ExecuteSWUAttack adds one of these on EVERY attack (Maul's double attack adds
//     it instead, never as well), and every phase start clears them — so the count is exactly 1 when this
//     On Attack resolves on the unit's first attack of the phase.
//   • Summed over every seat: the entry is written to the unit's controller at the time of each attack, so a
//     control change mid-phase would otherwise make the new controller's first attack look like the unit's.
//   • UniqueID is per object, so a Kylo that leaves play and returns is a new unit with a fresh count.
//   • The ready goes through OnReadyCard, which honours the can't-ready restrictions and ready taxes.
// Tests: SWUSim/Tests/Cases/ic27/KyloRen_Relentless.md

$onAttackAbilities["IC27_106:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $self = GetZoneObject($mzID);
    if (SWUObjGone($self)) return;
    $key = 'SWU_UNIT_ATTACKED_' . intval($self->UniqueID ?? 0);
    $attacks = 0;
    foreach (GetLiveSeatsArray() as $seat) $attacks += GlobalEffectCount(intval($seat), $key);
    if ($attacks !== 1) return;                                      // not his first attack this phase
    OnReadyCard(intval($player), $mzID);
};
