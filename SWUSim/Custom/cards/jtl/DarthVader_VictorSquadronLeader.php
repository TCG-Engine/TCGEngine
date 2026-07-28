<?php
// JTL_006
// Cost 6 - Darth Vader - Victor Squadron Leader - [Command,Villainy] - Power 5 - HP 6 - Upgrade Power 5 - Upgrade HP 5
// Text: Action [Exhaust]: If you attacked with a non-token Vehicle unit this phase, create a TIE Fighter token.
// DeployText: / Attached unit is a leader unit. / When deployed as an upgrade: Create 2 TIE Fighter tokens. /
// Epic Action: If you control 6 or more resources, choose one: / Deploy this leader. / Deploy this leader as an upgrade on a friendly Vehicle unit without a Pilot on it.

// JTL_006 Darth Vader — When deployed as an upgrade: Create 2 TIE Fighter tokens.
$whenPlayedAsUpgradeAbilities["JTL_006:0"] = function($player, $mzID) {
    SWUCreateUnitTokens(intval($player), 'JTL_T01', 2);
};

// JTL_006 Darth Vader — Leader Action [Exhaust]: If you attacked with a non-token Vehicle unit this
// phase, create a TIE Fighter token. The condition is the SWU_ATTACKED_VEHICLE flag (set in CombatLogic
// when a non-token Vehicle attacks). Either way the leader exhausts.
$leaderAbilities["JTL_006"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    if (GlobalEffectCount($player, 'SWU_ATTACKED_VEHICLE') > 0) {
        SWUCreateUnitToken($player, 'JTL_T01'); // TIE Fighter (Space, 1/1)
    }
    SWUAfterAction($player);
};
