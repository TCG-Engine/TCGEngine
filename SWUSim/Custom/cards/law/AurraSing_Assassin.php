<?php
// LAW_004
// Cost 7 - Aurra Sing - Assassin - [Vigilance,Villainy] - Power 3 - HP 7
// Text: Action [Exhaust]: Defeat a non-leader unit with 1 or less remaining HP.
// DeployText: When Deployed: You may defeat a non-leader unit with 5 or less remaining HP.
// Epic Action: If you control 7 or more resources, deploy this leader.

$leaderAbilities["LAW_004"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $targets = AurraSingAssassinTargets($player, 1);
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Defeat_a_non-leader_unit_with_1_or_less_remaining_HP", "DEFEAT_UNIT");
    SWUQueueAfterAction($player);
};

$whenPlayedAbilities["LAW_004:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'DEFEAT_UNIT', 'nonLeader' => true, 'may' => true,
        'extraFilter' => fn($o) => intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0) <= 5,
        'question' => "Defeat_a_non-leader_unit_with_5_or_less_remaining_HP?", 'prompt' => "Choose_a_unit",
    ]);
};

// ── LAW_004 Aurra Sing ────────────────────────────────────────────────────────
// Front Action [Exhaust]: defeat a non-leader unit with 1 or less remaining HP.
// Deployed When Deployed: you MAY defeat a non-leader unit with 5 or less remaining HP.
function AurraSingAssassinTargets(int $player, int $maxRemainingHP): array {
    global $playerID; $playerID = $player;
    $out = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, NonLeaderUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if (SWUObjGone($o)) continue;
            if (intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0) <= $maxRemainingHP) $out[] = $mz;
        }
    }
    return $out;
}
