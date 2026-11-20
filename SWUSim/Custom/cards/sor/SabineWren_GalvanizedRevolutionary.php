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
    // Deployed side: "On Attack: Deal 1 damage to EACH enemy base" — a fan-out. (The FRONT side reads
    // "each base"; this handler is the deployed On Attack.) GetOpponent() hit one seat and is null above
    // seat 2, so a far-seat Sabine dealt nothing at all.
    SWUSimultaneousBaseDamage(function () use ($player) { foreach (OpponentsOf(intval($player)) as $o) SWUDealDamageToBase(1, $o); });
};

// SOR_014 Sabine Wren — Leader Action [Exhaust]: Deal 1 damage to each base.
$leaderAbilities["SOR_014"] = function(int $player): void {
    // ⚠ "Deal 1 damage to EACH base" — EVERY base at the table, the caster's own included (that is what
    // separates the front side from the deployed one, which says "each ENEMY base"). This was written as
    // two literal calls, SWUDealDamageToBase(1, 1) and (1, 2) — a two-seat hardcode that NO scan for
    // OtherPlayer()/GetOpponent() can see, because it names seats as integers. At four seats it damaged
    // seats 1 and 2 and left 3 and 4 untouched, whoever the caster was. Found 2026-08-27.
    // One simultaneous event: two bases reaching 0 together is a draw (CR 6.3), not a win for the first seat checked.
    // Dealer stated explicitly (3rd arg), as SHD_159 does: on the caster's OWN base the funnel's fallback inference
    // credits an opponent, which hid that hit from the caster's HMW_185 Ty Yorrick.
    SWUSimultaneousBaseDamage(function () use ($player) { foreach (GetLiveSeatsArray() as $seat) SWUDealDamageToBase(1, $seat, intval($player)); });
    SWUAfterAction($player);
};
