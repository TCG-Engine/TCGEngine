<?php
// LOF_246
// Cost 3 - Grogu - Mysterious Child - [Heroism] - Power 1 - HP 6
// Text: Hidden / Action [Exhaust]: Heal up to 2 damage from a unit. If you do, deal that much damage to a unit.

// LOF_246 Grogu — Hidden + Action [Exhaust]: heal up to 2 from a unit. If you do, deal that much to a unit.
$unitAbilities["LOF_246"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = array_values(SWUAllUnits());
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Heal_up_to_2_from_a_unit", "LOF_246#0");
};

$customDQHandlers["LOF_246#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) { SWUAfterAction(intval($player)); return; }
    $o = GetZoneObject($lastDecision);
    $healAmt = $o ? min(2, intval($o->Damage ?? 0)) : 0;
    if ($healAmt <= 0) { SWUAfterAction(intval($player)); return; }
    OnHealUnit(intval($player), $lastDecision, $healAmt);
    $dtargets = array_values(SWUAllUnits());
    if (empty($dtargets)) { SWUAfterAction(intval($player)); return; }
    SWUQueueChooseTarget(intval($player), $dtargets, "Deal_{$healAmt}_damage_to_a_unit", "DEAL_UNIT_DAMAGE|{$healAmt}");
    SWUQueueAfterAction($player);
};
