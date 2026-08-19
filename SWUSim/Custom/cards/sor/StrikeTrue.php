<?php
// SOR_127
// Cost 3 - Strike True - [Command]
// Text: A friendly unit deals damage equal to its power to an enemy unit.

// SOR_127 Strike True — step 1: friendly dealer chosen ($lastDecision); collect
// enemy targets and carry the dealer mzID into step 2 via the handler param.
$customDQHandlers["SOR_127#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $friendlyMz = $lastDecision;
    $enemies = SWUAllUnits('their');
    if (empty($enemies)) return;
    // Same helper-text fix as LAW_008 Krennic, which reuses this continuation: say who is dealing and
    // how much, or the player is choosing a target with none of the information the choice needs.
    $dealer = GetZoneObject($friendlyMz);
    $dealerName = SWUObjGone($dealer) ? 'that_unit' : str_replace(' ', '_', SWUObjectTitle($dealer));
    $dealerPower = SWUObjGone($dealer) ? 0 : intval(ObjectCurrentPower($dealer));
    SWUQueueChooseTarget(intval($player), $enemies,
        "Choose_an_enemy_unit_for_{$dealerName}_to_deal_{$dealerPower}_damage_to",
        "SOR_127#1|" . $friendlyMz, 0);
};

// SOR_127 step 2: deal the dealer's current power to the chosen enemy ($lastDecision).
$customDQHandlers["SOR_127#1"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $fo = GetZoneObject($parts[0] ?? '');
    if (SWUObjGone($fo)) return;
    $power = intval(ObjectCurrentPower($fo));
    if ($power > 0) SWUDealDamageToUnit($lastDecision, $power, intval($player));
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_127:0"] = function($player, $mzID = '') {
// Strike True — "A friendly unit deals damage equal to its power to an enemy unit."
            $friendly = array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter),
                ZoneSearch("mySpaceArena",  AnyUnitFilter)
            );
            $enemy = array_merge(
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            );
            if (empty($friendly) || empty($enemy)) return; // needs both a dealer and a target
            SWUQueueChooseTarget(intval($player), $friendly,
                "Choose_a_friendly_unit_to_deal_damage_equal_to_its_power.",
                "SOR_127#0");
            return;
};
