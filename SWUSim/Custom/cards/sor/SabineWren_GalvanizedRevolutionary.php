<?php
// SOR_014
// Cost 4 - Sabine Wren - Galvanized Revolutionary - [Aggression,Heroism] - Power 2 - HP 5
// Text: Action [exhaust]: Deal 1 damage to each base.
// DeployText: On Attack: Deal 1 damage to each enemy base.
// Epic Action: If you control 4 or more resources, deploy this leader.

// ── SOR_014 Sabine Wren — Leader Unit On Attack ─────────────────────────────
// On Attack: Deal 1 damage to each enemy base.
// Non-interactive — fires and resolves automatically through the DQ drain.
$onAttackAbilities["SOR_014:0"] = function($player) {
    SWUDealDamageToBase(1, GetOpponent($player));
};

// SOR_014 Sabine Wren — Leader Action [Exhaust]: Deal 1 damage to each base.
$leaderAbilities["SOR_014"] = function(int $player): void {
    SWUDealDamageToBase(1, 1);
    SWUDealDamageToBase(1, 2);
    SWUAfterAction($player);
};
