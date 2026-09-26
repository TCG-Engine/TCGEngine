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
    // ⚠ FRIENDLY spans the TEAM (user ruling 2026-08-25, IBH_095): a teammate's unit is friendly,
    // so this pool is SWUFriendlyUnits(). ⚠ NOT SWUControlledUnits() — "a unit you control", an
    // ability COST, and "attack with" all stay 'my'. Degrades to 'my' outside a team game.
    foreach (SWUFriendlyUnits(null, AnyUnitFilter) as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        // ⚠ The POOL above was widened to the team but this flag read was not, so a teammate's
        // attacker was OFFERED-then-filtered-out: SWU_ATTACKED_{uid} lives on the ATTACKER'S
        // controller's seat, never the caster's. Any-seat read, which also survives a control change.
        if (SWUUnitAttackedThisPhaseAnySeat($o)) $attacked[] = $mz;
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
    SWUOfferDiscountPlay(intval($player), ['discount'=>1, 'types'=>['Unit'],
        'filter'=>fn($cid)=>!empty(array_intersect($chosenKw, _SWUCardKeywordSet($cid))),
        'prompt'=>"Play_a_unit_sharing_a_keyword_(it_costs_1_less)"]);
};
