<?php
// SEC_014
// Cost 5 - Sly Moore - Cipher in the Dark - [Cunning,Villainy] - Power 3 - HP 6
// Text: Action [1 resource, Exhaust]: If there are 4 or more exhausted units in play, create a Spy token.
// DeployText: On Attack: You may deal 2 damage to an exhausted unit.
// Epic Action: If you control 5 or more resources, deploy this leader.

// SEC_014 Sly Moore (deployed) — On Attack: You may deal 2 damage to an exhausted unit.
$onAttackAbilities["SEC_014:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Status ?? 0) !== 1) $targets[] = $mz; // exhausted
        }
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Deal_2_damage_to_an_exhausted_unit?", "Deal_2_damage_to_an_exhausted_unit", "DEAL_UNIT_DAMAGE|2");
};

// ── SEC_014 Sly Moore ─────────────────────────────────────────────────────────
// Action [1 resource, Exhaust]: If there are 4 or more exhausted units in play, create a Spy token.
$leaderAbilities["SEC_014"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $exh = 0;
    foreach (GetLiveSeatsArray() as $p) {
        foreach (GetUnitsInPlay($p) as $u) {
            if ($u !== null && empty($u->removed) && intval($u->Status ?? 0) !== 1) $exh++;
        }
    }
    if ($exh >= 4) SWUCreateUnitToken($player, 'SEC_T01');   // Spy
    SWUAfterAction($player);
};
