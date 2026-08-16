<?php
// IBH_053
// Cost 6 - Darth Vader - Don't Fail Me Again - [Aggression,Villainy] - Power 3 - HP 8
// Text: Action [1 resource, Exhaust]: Deal 1 damage to a base.
// DeployText: On Attack: Deal 2 damage to a base.
// Epic Action: If you control 6 or more resources, deploy this leader. (Flip him, ready him, and move him to the ground arena.)

// IBH_053 Darth Vader (deployed) — On Attack: deal 2 damage to A BASE → the attacker chooses either
// base, exactly like his own leader-side Action below (which already offered the choice). The deployed
// side was hardcoded to the enemy base under the stale "a base choose can't survive an OnAttack"
// workaround documented on Rebellion Y-Wing; re-probed 2026-08-16 and false.
$onAttackAbilities["IBH_053:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUOfferBaseTarget(intval($player), [
        'continuation' => 'DEAL_BASE_DAMAGE', 'amount' => 2, 'baseSide' => 'any',
        'prompt' => "Deal_2_damage_to_a_base",
    ]);
};

// IBH_053 Darth Vader — Leader Action [1 resource, Exhaust]: deal 1 damage to a base.
$leaderAbilities["IBH_053"] = function(int $player): void {
    global $playerID; $playerID = $player;
    SWUOfferBaseTarget($player, ['continuation'=>'DEAL_BASE_DAMAGE','amount'=>1,'prompt'=>"Deal_1_damage_to_a_base"]);
    SWUQueueAfterAction($player);
};
