<?php
// LOF_246
// Cost 3 - Grogu - Mysterious Child - [Heroism] - Power 1 - HP 6
// Text: Hidden / Action [Exhaust]: Heal up to 2 damage from a unit. If you do, deal that much damage to a unit.

// LOF_246 Grogu — Hidden + Action [Exhaust]: heal up to 2 from a unit. If you do, deal that much to a unit.
$unitAbilities["LOF_246"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = array_values(SWUAllUnits());
    if (empty($targets)) { SWUAfterAction($player); return; }
    // USER RULING (2026-08-14): for an "up to N" effect the TARGET choice is MANDATORY and the soft
    // pass is choosing an amount of ZERO — so this is a plain MZCHOOSE and Heal0 is offered below.
    SWUQueueChooseTarget(intval($player), $targets, "Heal_up_to_2_from_a_unit", "LOF_246#0");
};

$customDQHandlers["LOF_246#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) { SWUAfterAction(intval($player)); return; }
    $o = GetZoneObject($lastDecision);
    $maxHeal = $o ? min(2, intval($o->Damage ?? 0)) : 0;
    if ($maxHeal <= 0) { SWUAfterAction(intval($player)); return; }   // nothing to heal → no amount to pick
    // "Heal UP TO 2" — the amount is the player's, and Heal0 is always available: it is the only soft
    // pass now that the target pick is mandatory, and it matters because healing forces the second
    // clause ("if you do, deal that much damage to a unit").
    $uid  = intval($o->UniqueID ?? 0);
    $opts = $maxHeal >= 2 ? "Heal0&Heal1&Heal2" : "Heal0&Heal1";
    DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", $opts, 1,
        tooltip: "Heal_up_to_{$maxHeal}_(you_then_deal_that_much_to_a_unit)");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LOF_246#1|{$uid}", 1);
};

$customDQHandlers["LOF_246#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $uid = intval($parts[0] ?? 0);
    $amt = ($lastDecision === 'Heal2') ? 2 : (($lastDecision === 'Heal0') ? 0 : 1);
    if ($amt <= 0) { SWUAfterAction(intval($player)); return; }      // the soft pass
    $mz = SWUFindMzByUID($uid);
    if ($mz === null) { SWUAfterAction(intval($player)); return; }
    OnHealUnit(intval($player), $mz, $amt);
    $dtargets = array_values(SWUAllUnits());
    if (empty($dtargets)) { SWUAfterAction(intval($player)); return; }
    SWUQueueChooseTarget(intval($player), $dtargets, "Deal_{$amt}_damage_to_a_unit", "DEAL_UNIT_DAMAGE|{$amt}");
    SWUQueueAfterAction($player);
};
