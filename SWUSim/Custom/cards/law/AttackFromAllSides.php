<?php
// LAW_207
// Cost 3 - Attack From All Sides - [Aggression]
// Text: Deal 3 damage to a unit. If there are 4 or more different aspects among friendly units, you may deal 5 damage to that unit instead.

// LAW_207 Attack From All Sides — step 0: deal 3 to the chosen unit, or (if 4+ different aspects among
// friendly units) offer to deal 5 instead.
$customDQHandlers["LAW_207#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $caster = intval($parts[0] ?? intval($player));
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $aspects = [];
    foreach (GetUnitsInPlay($caster) as $u) {
        if (!empty($u->removed)) continue;
        foreach (explode(',', (string)(CardAspect($u->CardID ?? '') ?? '')) as $a) { $a = trim($a); if ($a !== '') $aspects[$a] = true; }
    }
    if (count($aspects) >= 4) {
        $uid = intval($o->UniqueID ?? 0);
        DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Deal_5_instead_of_3?");
        DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LAW_207#1|" . $uid, 1);
        return;
    }
    SWUDealDamageToUnit($lastDecision, 3, intval($player));
};

$customDQHandlers["LAW_207#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $uid = intval($parts[0] ?? 0);
    $mz = SWUFindMzByUID($uid);
    if ($mz === null) return;
    SWUDealDamageToUnit($mz, $lastDecision === 'YES' ? 5 : 3, intval($player));
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LAW_207:0"] = function($player, $mzID = '') {
// Attack From All Sides — "Deal 3 damage to a unit. If there are 4 or more
                          // different aspects among friendly units, you may deal 5 damage to that unit
                          // instead."
            global $playerID; $playerID = intval($player);
            $units = SWUAllUnits();
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Choose_a_unit_to_deal_3_(or_5)", "LAW_207#0|" . intval($player));
            return;
};
