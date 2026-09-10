<?php
// HMW_042
// Cost 8 - Dooku, Corruption Must Be Eradicated - [Vigilance][Aggression] - Unit (Ground) 8/7
// Traits: Force, Jedi, Republic - unique
// Text: Overwhelm
//       When Played: You may ready another unit. If you do, heal damage from a base equal to that unit's cost.
//
// Overwhelm needs no code ($Overwhelm_Cards).
//
// USER RULING (2026-09-10): "ready another unit" is ANY unit other than Dooku — enemy units included (ready
// a high-cost, low-power enemy to heal more than it can swing back for, or your own high-cost unit).
//   • Pool: EXHAUSTED units only (readying a ready unit changes nothing — the SHD_189 Slaver's Freighter
//     shape), every seat and a teammate included (SWUAllUnits), Dooku excluded by UniqueID. He enters play
//     exhausted himself, so "another" is observable.
//   • "IF YOU DO" measures the OUTCOME: exhausted before AND ready after. A unit under a can't-ready effect
//     (SHD_193, LAW_077, SEC_037, SOR_186, LOF_098 in space) may still be chosen — OnReadyCard refuses it —
//     and then heals nothing. Deliberately not pre-filtered: the can't-ready gates live in OnReadyCard, and
//     re-deriving them here would be a second copy that drifts.
//   • "That unit's COST" is the PRINTED cost of the readied card (a token is 0; a deployed leader its
//     leader cost; a TWI_116 Clone copy the copied card's).
//   • "A base" is unqualified: any DAMAGED base on any side (SWUAllBaseMzIDs 'any' — every live seat and a
//     teammate). A lone damaged base is healed without a prompt (the mandatory choose auto-resolves); no
//     heal step at all when nothing is damaged or the cost is 0.
$whenPlayedAbilities["HMW_042:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $selfUID = SWUObjUID(GetZoneObject($mzID), 0);
    $targets = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o) || intval($o->UniqueID ?? 0) === $selfUID) continue;
        if (intval($o->Status ?? 1) === 0) $targets[] = $mz;
    }
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Ready_another_unit?", "Ready_another_unit_(heal_a_base_equal_to_its_cost)", "HMW_042#0");
};

$customDQHandlers["HMW_042#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject((string)$lastDecision);
    if (SWUObjGone($o) || intval($o->Status ?? 1) !== 0) return;
    $uid  = intval($o->UniqueID ?? 0);
    $cost = intval(CardCost($o->CardID ?? ''));
    OnReadyCard(intval($player), (string)$lastDecision);
    $playerID = intval($player);
    $mz = SWUFindMzByUID($uid);
    $after = $mz !== null ? GetZoneObject($mz) : null;
    if (SWUObjGone($after) || intval($after->Status ?? 0) !== 1) return;   // not readied — "if you do" fails
    if ($cost <= 0) return;
    $bases = [];
    foreach (SWUAllBaseMzIDs(intval($player), 'any') as $bmz) {
        $b = GetZoneObject($bmz);
        if (!SWUObjGone($b) && intval($b->Damage ?? 0) > 0) $bases[] = $bmz;
    }
    SWUQueueChooseTarget(intval($player), $bases, "Heal_{$cost}_damage_from_a_base", "HEAL_TARGET|{$cost}");
};
