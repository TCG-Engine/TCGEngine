<?php
// LOF_005
// Cost 5 - Morgan Elsbeth - Following the Call - [Command,Villainy] - Power 3 - HP 6
// Text: Action [Exhaust]: Choose a friendly unit that attacked this phase. Play a unit from your hand that shares a keyword with the chosen unit. It costs 1 resource less.
// DeployText: On Attack: The next unit you play this phase costs 1 resource less if it shares a keyword with a friendly unit.
// Epic Action: If you control 5 or more resources, deploy this leader.

// LOF_005 Morgan Elsbeth — On Attack: arm "the next unit you play this phase costs 1 less if it shares a
// keyword with a friendly unit" (SWU_LOF005_DISCOUNT_NEXT; applied in SWUComputePlayCost, spent at entry).
$onAttackAbilities["LOF_005:0"] = function($player, $mzID) {
    AddGlobalEffects(intval($player), 'SWU_LOF005_DISCOUNT_NEXT');
};

$leaderAbilities["LOF_005"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $attacked = [];
    foreach (array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter)) as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (GlobalEffectCount($player, 'SWU_ATTACKED_' . intval($o->UniqueID ?? -1)) > 0) $attacked[] = $mz;
    }
    if (empty($attacked)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $attacked, "Choose_a_friendly_unit_that_attacked_this_phase", "LOF_005#0");
};

$customDQHandlers["LOF_005#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) { SWUAfterAction(intval($player)); return; }
    $chosen = GetZoneObject($lastDecision);
    if (SWUObjGone($chosen)) { SWUAfterAction(intval($player)); return; }
    // The chosen unit is IN PLAY, so it counts its CURRENT keywords (printed + conditional + granted). The
    // hand candidates count PRINTED keywords only (cards don't have abilities / conditional keywords in hand).
    $chosenKw = _SWUCardKeywordSet($chosen->CardID ?? '');
    foreach (['Ambush'=>'AMBUSH','Grit'=>'GRIT','Hidden'=>'HIDDEN','Overwhelm'=>'OVERWHELM','Saboteur'=>'SABOTEUR','Sentinel'=>'SENTINEL','Shielded'=>'SHIELDED','Raid'=>'RAID','Restore'=>'RESTORE'] as $name => $kw) {
        if (!in_array($name, $chosenKw, true) && _SWUUnitHasKeyword($chosen, $kw)) $chosenKw[] = $name;
    }
    $targets = [];
    foreach (SWUHandPlayablesAtDiscount(intval($player), ['Unit'], 1) as $mz) {
        $h = GetZoneObject($mz);
        if (SWUObjGone($h)) continue;
        if (!empty(array_intersect($chosenKw, _SWUCardKeywordSet($h->CardID ?? '')))) $targets[] = $mz;
    }
    if (empty($targets)) { SWUAfterAction(intval($player)); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Play_a_unit_sharing_a_keyword_(it_costs_1_less)", "DISCOUNT_PLAY_FROM_HAND|1");
};
