<?php
// TWI_177
// Guerilla Insurgency
// Text: Each player defeats a resource they control and discards 2 cards from their hand. Deal 4 damage to each ground unit.

// When Played (event) — migrated from OnPlayEvent. ($cardID hardcoded for the caster self-exclude.)
$whenPlayedAbilities["TWI_177:0"] = function($player, $mzID = '') {
    global $playerID;
    $playerID = intval($player);
    // 1. EACH PLAYER defeats a resource THEY control — so each player picks their OWN. Every LIVE seat,
    //    caster included — was the literal [caster, OtherPlayer(caster)], i.e. two seats.
    //    ⚠ The old comment here read "fungible → auto-pick the first". Resources are NOT fungible (USER
    //    RULING 2026-08-26) — which one dies is information the player can act on — and "a resource THEY
    //    control" names the owner as the decider, not the caster. Both halves were wrong.
    foreach (GetLiveSeatsArray() as $p) {
        $playerID = $p;
        SWUQueueResourceDefeatPick($p, ZoneSearch("myResources", null), 1, "Choose_a_resource_to_defeat_(Guerilla_Insurgency)");
    }
    // 2. Each OPPONENT discards 2 via the helper, one call per seat; the caster's own discard is handled
    //    inline below because it must exclude the just-played event.
    foreach (OpponentsOf(intval($player)) as $opp) {
        SWUDiscardCards(intval($player), 2, $opp);
    }
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
