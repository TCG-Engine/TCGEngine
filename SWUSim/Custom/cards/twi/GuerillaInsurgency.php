<?php
// TWI_177
// Guerilla Insurgency
// Text: Each player defeats a resource they control and discards 2 cards from their hand. Deal 4 damage to each ground unit.

// When Played (event) — migrated from OnPlayEvent. ($cardID hardcoded for the caster self-exclude.)
$whenPlayedAbilities["TWI_177:0"] = function($player, $mzID = '') {
    global $playerID;
    $playerID = intval($player);
    $opp = OtherPlayer(intval($player));
    // 1. Each player defeats a resource they control (fungible → auto-pick the first).
    foreach ([intval($player), $opp] as $p) {
        $playerID = $p;
        $res = ZoneSearch("myResources", null);
        if (!empty($res)) SWUDefeatResource($p, $res[0]);
    }
    // 2. Each player discards 2 (opponent via helper; caster inline, excluding the just-played event).
    SWUDiscardCards(intval($player), 2); // makes the opponent discard 2
    $playerID = intval($player);
    $casterCards = [];
    $excluded = false;
    foreach (array_values(ZoneSearch("myHand")) as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (!$excluded && ($o->CardID ?? '') === 'TWI_177') { $excluded = true; continue; } // skip the event itself
        $casterCards[] = $o;
    }
    if (count($casterCards) <= 2) {
        foreach ($casterCards as $o) { $o->Remove(); SWUAddToDiscard(intval($player), $o->CardID, 'HAND'); }
    } else {
        for ($n = 0; $n < 2; $n++) {
            DecisionQueueController::AddDecision(intval($player), "MZCHOOSE", "myHand", 1, tooltip: "Choose_card_to_discard");
            DecisionQueueController::AddDecision(intval($player), "CUSTOM", "DISCARD_FROM_OWN_HAND|" . intval($player), 1);
        }
    }
    // 3. Deal 4 to each ground unit (both players; UID-snapshot).
    $playerID = intval($player);
    $uids = [];
    foreach (['myGroundArena', 'theirGroundArena'] as $z) {
        foreach (ZoneSearch($z, ['Unit', 'Token Unit', 'Leader Unit']) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID ?? 0);
        }
    }
    foreach ($uids as $uid) { $mz = SWUFindMzByUID($uid); if ($mz !== null) SWUDealDamageToUnit($mz, 4, intval($player)); }
};
