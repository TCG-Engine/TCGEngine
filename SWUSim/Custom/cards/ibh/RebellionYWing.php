<?php
// IBH_006
// Cost 3 - Rebellion Y-Wing - [Cunning,Heroism] - Power 2 - HP 3
// Text: On Attack: Deal 1 damage to a base.

// IBH_006 / IBH_024 / IBH_032 Rebellion Y-Wing — On Attack: deal 1 damage to A BASE, so the attacker
// CHOOSES either base (no controller word), matching LAW_058 / HMW_177 / LAW_184.
//
// This used to deal the damage straight to the enemy base, justified by a comment claiming a 2-base
// MZCHOOSE could not survive an OnAttack when the attack was on the base directly (no combat pause).
// Re-probed 2026-08-16: that is NOT true — a mandatory base choose queued here survives BOTH a direct
// base attack and an attack on a unit. The stale workaround had spread to IBH_053 and LAW_184, so all
// three were silently denying the choice.
$onAttackAbilities["IBH_006:0"] =
$onAttackAbilities["IBH_024:0"] =
$onAttackAbilities["IBH_032:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUOfferBaseTarget(intval($player), [
        'continuation' => 'DEAL_BASE_DAMAGE', 'amount' => 1, 'baseSide' => 'any',
        'prompt' => "Deal_1_damage_to_a_base",
    ]);
};
