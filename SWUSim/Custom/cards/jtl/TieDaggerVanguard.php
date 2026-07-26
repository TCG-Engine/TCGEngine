<?php
// JTL_239
// Cost 3 - TIE Dagger Vanguard - [Villainy] - Power 2 - HP 2
// Text: When Played: You may deal 2 damage to a damaged unit.

// ── JTL_239 TIE Dagger Vanguard — When Played: You may deal 2 damage to a damaged unit. ──────────────
$whenPlayedAbilities["JTL_239:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval($o->Damage) > 0) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_deal_2_to_a_damaged_unit", "Deal_2_to_a_damaged_unit", "DEAL_UNIT_DAMAGE|2");
};
