<?php
// SOR_015
// Cost 5 - Boba Fett - Collecting the Bounty - [Cunning,Villainy] - Power 4 - HP 7
// Text: When an enemy unit leaves play: You may exhaust this leader. If you do, ready a resource.
// DeployText: When this unit completes an attack: If an enemy unit left play this phase, ready up to 2 resources.
// Epic Action: If you control 5 or more resources, deploy this leader.

// SOR_015 Boba Fett (deployed): "When this unit completes an attack: If an enemy unit left play
// this phase, ready up to 2 resources." The SWU_ENEMY_LEFT_PLAY flag is set on Boba's controller
// when an enemy leaves play (incl. the defender Boba just defeated, set before this fires).
$onAttackEndAbilities["SOR_015:0"] = function($player, $mzID) {
    if (GlobalEffectCount(intval($player), 'SWU_ENEMY_LEFT_PLAY') > 0) {
        SWUReadyResources(intval($player), 2);
    }
};
