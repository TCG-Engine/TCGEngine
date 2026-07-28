<?php
// IBH_006
// Cost 3 - Rebellion Y-Wing - [Cunning,Heroism] - Power 2 - HP 3
// Text: On Attack: Deal 1 damage to a base.

// IBH_006 / IBH_024 / IBH_032 Rebellion Y-Wing — On Attack: deal 1 damage to a base. Dealt directly to
// the ENEMY base (the only meaningful target for an attacker). NOTE: a 2-base MZCHOOSE is NOT viable in
// an OnAttack — the choice survives only with a combat pause (attacking a unit) and is silently dropped
// when attacking the base directly (no pause → the OnAttack $playerID-restore skips the mandatory pick).
$onAttackAbilities["IBH_006:0"] =
$onAttackAbilities["IBH_024:0"] =
$onAttackAbilities["IBH_032:0"] = function($player, $mzID) {
    SWUDealDamageToBase(1, OtherPlayer(intval($player)));
};
