<?php
// SEC_011
// Cost 6 - Governor Pryce - Tyrant of Lothal - [Aggression,Villainy] - Power 4 - HP 6
// Text: Action [1 resource, Exhaust]: Ready a token unit.
// DeployText: This unit gets +1/+0 for each ready friendly token unit. / On Attack: Create a Spy token.
// Epic Action: If you control 6 or more resources, deploy this leader.

// SEC_011 Governor Pryce (deployed) — On Attack: Create a Spy token. (The +1/+0-per-ready-token passive
// lives in ObjectCurrentPower; this is the second deploy-side line.)
$onAttackAbilities["SEC_011:0"] = function($player, $mzID) {
    SWUCreateUnitToken(intval($player), 'SEC_T01');   // Spy (enters exhausted, so it doesn't self-buff power)
};

// ── SEC_011 Governor Pryce ────────────────────────────────────────────────────
// Action [1 resource, Exhaust]: Ready a token unit. (Offers friendly exhausted token units.)
$leaderAbilities["SEC_011"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; }
    $tokens = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && EffectiveCardType($o) === 'Token Unit'
                    && intval($o->Status ?? 0) !== 1) $tokens[] = $mz;
        }
    }
    if (empty($tokens)) { SWUAfterAction($player); return; } // gate should prevent
    SWUQueueChooseTarget($player, $tokens, "Ready_a_token_unit", "READY_UNIT");
    SWUQueueAfterAction($player);
};
