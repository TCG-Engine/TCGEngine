<?php
// JTL_180
// Cost 3 - Piercing Shot - [Aggression]
// Text: Defeat all Shield tokens on a unit. Deal 3 damage to that unit.

// ── JTL_180 Piercing Shot — defeat all Shield tokens (SOR_T02) on the chosen unit, then deal 3 to it. ──
$customDQHandlers["JTL_180#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) return;
    if (is_array($obj->Subcards ?? null)) {
        foreach ($obj->Subcards as &$sub) {
            $scid = is_array($sub) ? ($sub['CardID'] ?? '') : ($sub->CardID ?? '');
            if ($scid === 'SOR_T02') { if (is_array($sub)) $sub['removed'] = true; else $sub->removed = true; }
        }
        unset($sub);
    }
    DecisionQueueController::CleanupRemovedCards();
    SWUDealDamageToUnit($lastDecision, 3, intval($player));
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_180:0"] = function($player, $mzID = '') {
// Piercing Shot — "Defeat all Shield tokens on a unit. Deal 3 damage to it."
            global $playerID;
            $playerID = intval($player);
            // ⚠ UNQUALIFIED pool = the WHOLE table. NOT my*+their*: `their*` excludes a Team Suns
            // teammate, so that pairing leaves their units in NEITHER list and they silently drop out
            // of the pool. SWUAllUnits() starts from 'team' (degrades to 'my' outside a team game, so
            // Premier is byte-identical). See memory: unqualified pools miss teammates.
            $targets = SWUAllUnits();
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Defeat_all_Shields_then_deal_3", "JTL_180#0");
            return;
};
