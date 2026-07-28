<?php
// IBH_053
// Cost 6 - Darth Vader - Don't Fail Me Again - [Aggression,Villainy] - Power 3 - HP 8
// Text: Action [1 resource, Exhaust]: Deal 1 damage to a base.
// DeployText: On Attack: Deal 2 damage to a base.
// Epic Action: If you control 6 or more resources, deploy this leader. (Flip him, ready him, and move him to the ground arena.)

// IBH_053 Darth Vader (deployed) — On Attack: deal 2 damage to a base (enemy base directly).
$onAttackAbilities["IBH_053:0"] = function($player, $mzID) {
    SWUDealDamageToBase(2, OtherPlayer(intval($player)));
};

// IBH_053 Darth Vader — Leader Action [1 resource, Exhaust]: deal 1 damage to a base.
$leaderAbilities["IBH_053"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; }
    SWUOfferBaseTarget($player, ['continuation'=>'DEAL_BASE_DAMAGE','amount'=>1,'prompt'=>"Deal_1_damage_to_a_base"]);
    SWUQueueAfterAction($player);
};
