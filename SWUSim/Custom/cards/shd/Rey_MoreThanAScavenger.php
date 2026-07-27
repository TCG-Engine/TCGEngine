<?php
// SHD_004
// Cost 6 - Rey - More Than a Scavenger - [Heroism,Vigilance] - Power 2 - HP 6
// Text: Action [1 resource, Exhaust]: Give an Experience token to a unit with 2 or less power.
// DeployText: Restore 3 (When this unit attacks, heal 3 damage from your base.) / On Attack: You may give an Experience token to a unit with 2 or less power.
// Epic Action: If you control 6 or more resources, deploy this leader.

$leaderAbilities["SHD_004"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; }
    $targets = ReyMoreThanaScavengerLowPowerTargets($player);
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Give_an_Experience_token_to_a_unit_with_2_or_less_power", "GIVE_EXPERIENCE|1");
    SWUQueueAfterAction($player);
};

$onAttackAbilities["SHD_004:0"] = function($player, $mzID) {
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'GIVE_EXPERIENCE', 'may' => true,
        'extraFilter' => fn($o) => intval(ObjectCurrentPower($o)) <= 2,
        'question' => "Give_an_Experience_token_to_a_unit_with_2_or_less_power?",
        'prompt'   => "Choose_a_unit",
    ]);
};

// ── SHD_004 Rey ────────────────────────────────────────────────────────────────
// Front Action [1 resource, Exhaust]: Give an Experience token to a unit with 2 or less power.
// Deployed: Restore 3 (keyword) + On Attack: You may give an Experience token to a unit with ≤2 power.
function ReyMoreThanaScavengerLowPowerTargets(int $player): array {
    $t = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval(ObjectCurrentPower($o)) <= 2) $t[] = $mz;
        }
    }
    return $t;
}
