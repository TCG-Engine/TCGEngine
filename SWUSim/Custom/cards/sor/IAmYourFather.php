<?php
// SOR_233
// Cost 3 - I Am Your Father - [Villainy]
// Text: Deal 7 damage to an enemy unit unless its controller says "no." If they do, draw 3 cards.

// SOR_233 I Am Your Father — the caster picked the enemy unit ($lastDecision). Offer its controller a
// YESNO to refuse the 7 damage; the branch resolves in SOR_233#1. Carry the target by UniqueID so a
// later board change can't stale the mzID.
$customDQHandlers["SOR_233#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $caster = intval($player);
    $playerID = $caster;
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $uid = intval($o->UniqueID);
    $controller = intval($o->Controller ?? OtherPlayer($caster));
    DecisionQueueController::AddDecision($controller, "YESNO", "-", 1,
        tooltip:"Say_no_to_the_7_damage?_(opponent_draws_3)");
    DecisionQueueController::AddDecision($controller, "CUSTOM", "SOR_233#1|{$caster}|{$uid}", 1);
};

$customDQHandlers["SOR_233#1"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $caster = intval($parts[0] ?? 0);
    $uid    = intval($parts[1] ?? 0);
    if ($lastDecision === "YES") {                 // controller refuses → no damage, caster draws 3
        $playerID = $caster;
        DoDrawCard($caster, 3);
        return;
    }
    $playerID = $caster;                           // controller allows → deal 7 to the unit
    $mz = SWUFindMzByUID($uid);
    if ($mz !== null) SWUDealDamageToUnit($mz, 7, $caster);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_233:0"] = function($player, $mzID = '') {
// I Am Your Father — "Deal 7 damage to an enemy unit unless its controller
                          // says 'no.' If they do, draw 3 cards." Caster picks the enemy unit; the
                          // unit's controller then gets a YESNO (refuse the damage → caster draws 3).
            global $playerID;
            $playerID = intval($player);
            $enemies = array_merge(
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            );
            if (empty($enemies)) return;   // no enemy unit → fizzle
            SWUQueueChooseTarget(intval($player), $enemies, "Choose_an_enemy_unit", "SOR_233#0");
            return;
};
