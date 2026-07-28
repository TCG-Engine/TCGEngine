<?php
// JTL_035
// Cost 3 - Tam Ryvora - Searching For Purpose - [Vigilance,Villainy] - Power 2 - HP 5 - Upgrade Power 2 - Upgrade HP 2
// Text: / Piloting [2 resources Vigilance Villainy] (You may play this as an upgrade on a friendly Vehicle without a Pilot.) / Attached unit gains: "On Attack: Give an enemy unit in this arena -1/-1 for this phase."

// JTL_035 Tam Ryvora (pilot) — granted "On Attack: Give an enemy unit in this arena -1/-1 for this phase."
$onAttackAbilities["JTL_035:0"] = function($player, $mzID) {
    $host = GetZoneObject($mzID);
    if (SWUObjGone($host)) return;
    $arena = ($host->Location ?? 'GroundArena');          // 'GroundArena' or 'SpaceArena' — "this arena"
    $arenaKey = (strpos($arena, 'Space') !== false) ? 'Space' : 'Ground';
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'APPLY_PHASE_DEBUFF|1|1|JTL_035', 'side' => 'their', 'arena' => $arenaKey,
        'prompt' => "Give_an_enemy_unit_in_this_arena_-1/-1",
    ]);
};
