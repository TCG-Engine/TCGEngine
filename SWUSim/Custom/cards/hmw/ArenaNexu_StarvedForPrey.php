<?php
// HMW_182
// Cost 4 - Arena Nexu - Starved For Prey - [Aggression] - Unit (Ground) 2/6
// Traits: Creature - Unique
// Text: Grit
//       On Attack: You may deal 3 damage to a friendly Creature unit (including this one) and ready this unit.
//       Use this ability only once each round.
//
// Grit is registry-wired ($Grit_Cards). It is what makes the self-pick worth it: the On Attack resolves
// before combat damage, so 3 on itself is +3 power for THIS attack.
//
// "Deal 3 … AND ready this unit" — joined by "and", not "If you do": the ready does not depend on the
// damage landing (LOF_108 Malakili prevents a friendly Creature's damage to a friendly unit, and the Nexu
// still readies). The damage is dealt with the Nexu as its SOURCE so Malakili can see it.
//
// "Use this ability only once each round" — the budget belongs to THIS copy, keyed by UniqueID, and is
// spent only on an accepted pick (USER RULING 2026-09-07: a decline spends nothing), the SHD_239 Toro
// Calican shape. A spent round means no offer at all.
// "Friendly" is the team in Team Suns — _SWUCollectUnitTargets' 'friendly' side includes teammates.

$onAttackAbilities["HMW_182:0"] = function ($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject((string)$mzID);
    if (SWUObjGone($self)) return;
    $uid = intval($self->UniqueID ?? 0);
    if (SWUUnitRoundUseSpent($uid)) return;
    $pool = _SWUCollectUnitTargets(intval($player), ['side' => 'friendly', 'traits' => 'Creature']);
    if (empty($pool)) return;
    SWUQueueMayChooseTarget(intval($player), $pool,
        "Deal_3_damage_to_a_friendly_Creature_unit_and_ready_this_unit?",
        "Deal_3_damage_to_a_friendly_Creature_unit_and_ready_this_unit", "HMW_182#0|{$uid}");
};

// $parts[0] = the Nexu's UniqueID. Everything is re-resolved by UID: the 3 damage can defeat a unit (the
// Nexu included) and re-index the arena.
$customDQHandlers["HMW_182#0"] = function ($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision) || SWUObjGone(GetZoneObject((string)$lastDecision)))
        return;                                                   // declined — the round is not spent
    $nexuUID = intval($parts[0] ?? 0);
    SWUSpendUnitRoundUse($nexuUID);                              // once each round, THIS copy — on actual use
    $srcMz = SWUFindMzByUID($nexuUID);
    SWUDealDamageToUnit((string)$lastDecision, 3, intval($player), $srcMz);
    $playerID = intval($player);
    $nexuMz = SWUFindMzByUID($nexuUID);                          // gone if it picked itself and died
    if ($nexuMz !== null) OnReadyCard(intval($player), $nexuMz);  // "and" — not gated on the damage
};
