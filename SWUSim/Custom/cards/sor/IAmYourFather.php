<?php
// SOR_233
// Cost 3 - I Am Your Father - [Villainy]
// Text: Deal 7 damage to an enemy unit unless its controller says "no." If they do, draw 3 cards.

// SOR_233 I Am Your Father — the caster picked the enemy unit ($lastDecision). Offer its controller the
// two outcomes as buttons (owner 2026-11-09: prompt "Choose an option", buttons "<card> Takes 7 Damage" /
// "Opponent Draws 3 Cards"); the branch resolves in SOR_233#1. The card is a BARE token "[[CardID]]" —
// OptionChooseUI draws it as the card's name with a hover preview; a title would put spaces/"&" into the
// delimited Param. Carry the target by UniqueID so a later board change can't stale the mzID.
$customDQHandlers["SOR_233#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $caster = intval($player);
    $playerID = $caster;
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $uid = intval($o->UniqueID);
    // The Controller field is the answer; the fallback is only for an unset field, and it must still
    // name the RIGHT seat above two seats — derive it from the mzID rather than OtherPlayer().
    $controller = intval($o->Controller ?? 0);
    if ($controller <= 0) $controller = SWUMzOwner((string)$lastDecision, $caster);
    DecisionQueueController::AddDecision($controller, "OPTIONCHOOSE",
        "[[" . strval($o->CardID) . "]]_Takes_7_Damage&Opponent_Draws_3_Cards", 1, tooltip:"Choose_an_option");
    DecisionQueueController::AddDecision($controller, "CUSTOM", "SOR_233#1|{$caster}|{$uid}", 1);
};

$customDQHandlers["SOR_233#1"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $caster = intval($parts[0] ?? 0);
    $uid    = intval($parts[1] ?? 0);
    if ($lastDecision === "Opponent_Draws_3_Cards") {   // controller refuses → no damage, caster draws 3
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
