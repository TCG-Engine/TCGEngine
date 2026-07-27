<?php
// SEC_069
// Cost 1 - Nimble Prowess - [Vigilance] - Upgrade Power 1 - Upgrade HP 1
// Text: Attach to a friendly unit. / When Played: You may exhaust a unit in attached unit's arena.

// SEC_069 Nimble Prowess (upgrade, +1/+1) — When Played: you may exhaust a unit in attached unit's
// arena. $mzID is the host's arena mzID.
$whenPlayedAbilities["SEC_069:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if (SWUObjGone($host)) return;
    $arena = $host->Location ?? 'GroundArena';   // 'GroundArena' | 'SpaceArena'
    // Only READY units can be exhausted — an already-exhausted unit is not a valid target, so it must
    // not be offered (else the "may exhaust" prompt fires with nothing meaningful to do). No ready unit
    // in the arena → auto-pass.
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'EXHAUST_UNIT', 'arena' => str_replace('Arena', '', $arena), 'may' => true,
        'extraFilter' => fn($o) => intval($o->Status ?? 0) === 1,
        'question' => "Exhaust_a_unit_in_attached_unit's_arena?", 'prompt' => "Choose_a_unit",
    ]);
};
