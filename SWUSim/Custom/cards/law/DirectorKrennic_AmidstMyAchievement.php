<?php
// LAW_008
// Cost 7 - Director Krennic - Amidst My Achievement - [Command,Villainy] - Power 4 - HP 9
// Text: Action [Exhaust, defeat a friendly unit]: Create a Credit token.
// DeployText: When Deployed: Another friendly unit deals damage equal to its power to an enemy unit.
// Epic Action: If you control 7 or more resources, deploy this leader.

// ── LAW_008 Director Krennic ──────────────────────────────────────────────────
// Front Action [Exhaust, defeat a friendly unit]: create a Credit token.
$customDQHandlers["LAW_008#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    SWUDefeatUnit(intval($player), $lastDecision);   // pay the [defeat a friendly unit] cost
    SWUCreateCreditToken(intval($player), 1);
};

$leaderAbilities["LAW_008"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $friendly = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z)
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) { $o = GetZoneObject($mz); if ($o !== null && empty($o->removed)) $friendly[] = $mz; }
    if (empty($friendly)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $friendly, "Defeat_a_friendly_unit_to_create_a_Credit", "LAW_008#0");
    SWUQueueAfterAction($player);
};

// Deployed When Deployed: another friendly unit deals damage equal to its power to an enemy unit.
$whenPlayedAbilities["LAW_008:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUid = SWUObjUID($self, 0);
    $friendly = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z)
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $fo = GetZoneObject($mz);
            if ($fo !== null && empty($fo->removed) && intval($fo->UniqueID ?? 0) !== $selfUid) $friendly[] = $mz;
        }
    $enemyExists = !empty(ZoneSearch('theirGroundArena', AnyUnitFilter)) || !empty(ZoneSearch('theirSpaceArena', AnyUnitFilter));
    if (empty($friendly) || !$enemyExists) return;
    // Helper text: name the whole effect, not just the click. "Choose another friendly unit to deal its
    // power" reads as if the unit is being spent or attacking; the player is picking a DEALER for a
    // second, separate choice they have not been told about yet.
    SWUQueueChooseTarget(intval($player), $friendly,
        "Choose_another_friendly_unit_to_deal_damage_equal_to_its_power.",
        "LAW_008#1");
};

$customDQHandlers["LAW_008#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    $enemies = array_merge(ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter));
    if (empty($enemies)) return;
    // Step 2 carries the dealer's NAME and CURRENT power, so the player can see exactly how much damage
    // they are about to assign — a bare "Choose an enemy unit" gives no way to judge the target.
    // Current power, not printed: upgrades and buffs are what make this trigger swing.
    $dealer = GetZoneObject($lastDecision);
    $dealerName = SWUObjGone($dealer) ? 'that_unit' : str_replace(' ', '_', SWUObjectTitle($dealer));
    $dealerPower = SWUObjGone($dealer) ? 0 : intval(ObjectCurrentPower($dealer));
    SWUQueueChooseTarget(intval($player), $enemies,
        "Choose_an_enemy_unit_for_{$dealerName}_to_deal_{$dealerPower}_damage_to",
        "SOR_127#1|" . $lastDecision, 0);  // reuse SOR_127#1 deal-power
};
