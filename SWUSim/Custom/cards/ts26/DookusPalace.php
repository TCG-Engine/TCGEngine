<?php
// TS26_10
// Dooku's Palace - [Command] - HP 27
// Text: 
// Epic Action: Play a unit from your hand. It costs 1 resource less for each friendly leader unit.

// TS26_10 Dooku's Palace — Epic Action: play a unit from your hand; it costs 1 less per friendly leader
// unit. (DISCOUNT_PLAY_FROM_HAND|N owns the after-action.)
$baseAbilities["TS26_10"] = function($player) {
    global $playerID; $savedPID = $playerID; $playerID = intval($player);
    $n = 0;
    // "for each FRIENDLY leader unit" spans the TEAM (user ruling 2026-08-26). ⚠ This card was
    // MISSED by the 2026-08-26 friendly audit: that sweep matched an '// EpicAction:' header and
    // this clause sits on '// Epic Action:' — with a space. Only two cards hid behind that typo
    // (this one and its sibling palace); Lando JTL_?? was a false positive and correctly uses
    // SWUControlledUnits for its "if YOU CONTROL" clause.
    foreach (SWUFriendlyUnitObjects(intval($player)) as $u) { if (empty($u->removed) && IsLeaderUnit($u)) $n++; }
    $ready = SWUTotalPaymentCapacity(intval($player));
    $eligible = [];
    foreach (ZoneSearch("myHand", ["Unit"]) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && max(0, SWUComputePlayCost(intval($player), $o) - $n) <= $ready) $eligible[] = $mz;
    }
    $playerID = $savedPID;
    if (empty($eligible)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $eligible, "Play_a_unit_from_hand_(-1_per_friendly_leader_unit)", "DISCOUNT_PLAY_FROM_HAND|{$n}");
};
