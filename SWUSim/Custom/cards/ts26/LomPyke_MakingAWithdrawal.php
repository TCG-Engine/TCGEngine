<?php
// TS26_51
// Cost 5 - Lom Pyke - Making a Withdrawal - [Command,Villainy] - Power 5 - HP 5
// Text: When Played: In player order, each opponent may heal 5 damage from their base. For each player that does, give 2 Experience tokens to a unit.

// TS26_51 Lom Pyke — When Played: in player order, each opponent may heal 5 from their base; for each
// that does, give 2 Experience tokens to a unit. (2-player: the single opponent.)
$whenPlayedAbilities["TS26_51:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $opp = OtherPlayer(intval($player));
    $b = GetBase($opp);
    if (empty($b) || !isset($b[0]) || intval($b[0]->Damage ?? 0) <= 0) return;   // no damage → no meaningful heal
    DecisionQueueController::AddDecision($opp, "YESNO", "-", 1, tooltip: "Heal_5_damage_from_your_base?");
    DecisionQueueController::AddDecision($opp, "CUSTOM", "TS26_51#0|" . intval($player), 1);
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
