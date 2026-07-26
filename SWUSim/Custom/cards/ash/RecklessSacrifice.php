<?php
// ASH_163
// Cost 2 - Reckless Sacrifice - [Aggression,Heroism]
// Text: Discard a unit from your hand. Deal 5 damage to a unit that costs more than the discarded card.

// ASH_163 Reckless Sacrifice (event) — discard the chosen hand unit, then deal 5 to a unit costing MORE
// than the discarded card. Read the cost before discarding so the comparison is against the right value.
$customDQHandlers["ASH_163#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $discardedCost = intval(CardCost($o->CardID ?? ''));
    DoDiscardCard(intval($player), $lastDecision);
    $tg = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $u = GetZoneObject($mz);
            if ($u !== null && empty($u->removed) && intval(CardCost($u->CardID ?? '')) > $discardedCost) $tg[] = $mz;
        }
    }
    if (empty($tg)) return;   // no costlier unit → the damage fizzles (the unit is still discarded)
    SWUQueueChooseTarget(intval($player), $tg, "Deal_5_to_a_unit_costing_more_than_the_discarded_card", "DEAL_UNIT_DAMAGE|5");
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["ASH_163:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $handUnits = [];
    foreach (ZoneSearch("myHand", ["Unit", "Token Unit"]) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $handUnits[] = $mz;
    }
    if (empty($handUnits)) return;   // no unit to discard → fizzle
    SWUQueueChooseTarget(intval($player), $handUnits, "Discard_a_unit_from_your_hand", "ASH_163#0");
};
