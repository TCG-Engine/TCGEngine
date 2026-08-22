<?php
// TS26_51
// Cost 5 - Lom Pyke - Making a Withdrawal - [Command,Villainy] - Power 5 - HP 5
// Text: When Played: In player order, each opponent may heal 5 damage from their base. For each player that does, give 2 Experience tokens to a unit.

// TS26_51 Lom Pyke — When Played: in player order, each opponent may heal 5 from their base; for each
// that does, give 2 Experience tokens to a unit. (2-player: the single opponent.)
// "IN PLAYER ORDER, EACH OPPONENT may heal 5" — one independent offer per live opponent, queued in
// player order so the table resolves around it. Was OtherPlayer(): a single seat, so at four seats two
// opponents were never offered the heal and the caster could never earn their Experience.
// ⚠ "For each player that does" makes this a PER-SEAT rider: each accepting opponent earns the caster
//   its own separate "give 2 Experience" — three acceptances mean three grants, not one.
// Offered only to seats with damage to heal, so nobody is asked a question whose only answer does
// nothing (and the caster is not handed Experience for a no-op heal).
$whenPlayedAbilities["TS26_51:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    foreach (SWUSeatsInPlayerOrder(intval($player)) as $seat) {
        if ($seat === intval($player)) continue;      // "each OPPONENT" — never the caster
        $b = GetBase($seat);
        if (empty($b) || !isset($b[0]) || intval($b[0]->Damage ?? 0) <= 0) continue;   // nothing to heal
        DecisionQueueController::AddDecision($seat, "YESNO", "-", 1, tooltip: "Heal_5_damage_from_your_base?");
        DecisionQueueController::AddDecision($seat, "CUSTOM", "TS26_51#0|" . intval($player), 1);
    }
};

$customDQHandlers["TS26_51#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $caster = intval($parts[0] ?? 0);
    if ($lastDecision !== 'YES') return;   // declined → no heal, no Experience
    $playerID = intval($player);
    OnHealBase(intval($player), intval($player), 5);   // the opponent heals their own base
    // the caster gives 2 Experience to a unit; GiveTokenUpgrade sets/leaves $playerID = $caster
    GiveTokenUpgrade($caster, '', [
        'friendlyOnly' => false,
        'amount'       => 2,
        'prompt'       => "Give_2_Experience_to_a_unit",
    ]);
};
