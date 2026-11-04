<?php
// IC27_123
// Cost 8 - Jyn Erso - Her Father's Revenge - [Aggression,Heroism] - Power 8 - HP 7 - Rebel
// Text: Saboteur
//       When Played: Ready up to 3 units with combined cost 7 or less. They each gain Saboteur for this phase.
//
// SABOTEUR (printed) needs no code — IC27_123 is in $Saboteur_Cards (generator-derived).

// IC27_123 Jyn Erso — ONE weighted multi-select (the LOF_202 Mind Trick shape) with a count cap of 3 and a budget of 7.
// "units" names no controller, so the pool is the WHOLE table (SWUAllUnits: team + every opponent). Weight = PRINTED
// cost (CR: abilities that refer to cost use the printed cost; a leader unit's printed cost is its cost; a token's is 0).
// Ready AND exhausted units are offered: CR 1.e lets a ready card be chosen for a readying effect, and the Saboteur half
// still applies to it. Jyn herself (cost 8) can never fit the budget.
if (!function_exists('_SWUIc27123Weights')) {
    function _SWUIc27123Weights(int $player): array {
        global $playerID; $playerID = $player;
        $out = [];
        foreach (SWUAllUnits() as $mz) {
            $o = GetZoneObject($mz);
            if (SWUObjGone($o)) continue;
            $out[$mz] = intval(CardCost($o->CardID ?? ''));
        }
        return $out;
    }
}

$whenPlayedAbilities["IC27_123:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    SWUQueueBudgetMultiChoose(intval($player), _SWUIc27123Weights(intval($player)), 7, 'cost',
        "Ready_up_to_3_units_with_7_or_less_combined_cost", "IC27_123#0", 1, 3);
};

// The picks. Re-measured and re-capped server-side (the client cap is UX only). Each chosen unit is readied through
// OnReadyCard (can't-ready restrictions, the when-readies taxes) and gains Saboteur for this phase — the rider has no
// "If you do", so a unit that could not ready (Frozen in Carbonite) or was already ready still gains it.
$customDQHandlers["IC27_123#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $picks = SWUFilterBudgetAnswer($lastDecision, _SWUIc27123Weights(intval($player)), 7, 3);
    $uids = [];
    foreach ($picks as $mz) {
        $o = GetZoneObject($mz);
        if (!SWUObjGone($o)) $uids[] = intval($o->UniqueID ?? 0);
    }
    foreach ($uids as $uid) {
        $playerID = intval($player);
        $mz = SWUFindMzByUID($uid);
        if ($mz === null) continue;
        OnReadyCard(intval($player), $mz);
        $playerID = intval($player);
        $mz = SWUFindMzByUID($uid);
        if ($mz !== null) AddTurnEffect($mz, SWUMakeTurnEffect('SABOTEUR', [], SWU_DUR_PHASE, 'IC27_123'));
    }
};
