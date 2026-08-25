<?php
// JTL_227
// Cost 2 - Superheavy Ion Cannon - [Cunning] - Upgrade Power 0 - Upgrade HP 3
// Text: Attach to a Capital Ship or Transport unit. / Attached unit gains: "On Attack: You may exhaust a non-leader unit the defending player controls. If you do, deal indirect damage equal to its power to that player."

// ── JTL_227 Superheavy Ion Cannon — granted On Attack: may exhaust an enemy non-leader unit; if you do,
// deal indirect damage equal to its power to that player. ─────────────────────────────────────────────
$onAttackAbilities["JTL_227:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    // "a non-leader unit THE DEFENDING PLAYER controls" — one named seat, not "an enemy".
    // ZoneSearch('their…') fans out across EVERY live opponent above two seats (GameLogic.php's Twin
    // Suns branch), so the old pool offered bystanders' units the card may not legally touch. At two
    // seats the defender IS the only opponent, so the their* form stays byte-identical there (I1).
    $defSeat = SWUCurrentDefendingSeat(intval($player));
    $targets = (SeatCountForGame() > 2)
        ? array_values(array_merge(
            ZoneSearch("p{$defSeat}GroundArena", NonLeaderUnitFilter),
            ZoneSearch("p{$defSeat}SpaceArena",  NonLeaderUnitFilter)
          ))
        : array_values(array_merge(
            ZoneSearch('theirGroundArena', NonLeaderUnitFilter),
            ZoneSearch('theirSpaceArena',  NonLeaderUnitFilter)
          ));
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Exhaust_an_enemy_unit_(deal_indirect_equal_to_its_power)", "Choose_an_enemy_non-leader_unit", "JTL_227#0");
};

$customDQHandlers["JTL_227#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) return;
    $pow = max(0, intval(ObjectCurrentPower($obj)));
    $obj->Status = 0;   // exhaust the enemy unit
    // "…to THAT player" = the defending player, i.e. the controller of the unit just exhausted — never
    // OtherPlayer(), which above two seats named a bystander (and seat 1 for any far-seat attacker).
    // Resolve from the exhausted unit's own mzID so the two halves cannot disagree.
    if ($pow > 0) SWUDealIndirectDamage(intval($player), $pow, SWUMzOwner($lastDecision, intval($player)));
};
