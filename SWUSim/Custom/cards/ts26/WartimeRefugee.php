<?php
// TS26_43
// Cost 1 - Wartime Refugee - [Vigilance] - Power 2 - HP 3
// Text: On Attack: An opponent heals 1 damage from their base.

// TS26_43 Wartime Refugee — On Attack: an opponent heals 1 damage from their base.
$onAttackAbilities["TS26_43:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // "AN opponent" — the controller's choice. Auto-resolves to an invisible PASSPARAMETER at one
    // eligible opponent, so Premier is byte-identical (I1).
    // ⚠ $eligible is DELIBERATELY null — do NOT filter to "opponents whose base is damaged". The heal is
    // a DRAWBACK the controller pays for a 2/3 body at cost 1, so aiming it at an UNDAMAGED base to make
    // it heal 0 is the controller's best line. Filtering would force the drawback to land and nerf the
    // card. (Same reasoning as TWI_222; opposite of TS26_26/TWI_252 — decide from what the effect DOES
    // to the chosen seat, never from the sentence shape.)
    SWUQueueChooseOpponent(intval($player), 'TS26_43#0|' . intval($player),
        "Choose_an_opponent_to_heal_1_from_their_base");
};

$customDQHandlers["TS26_43#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $caster = intval($parts[0] ?? $player);
    $opp    = SWUPickedOpponent($lastDecision);
    if ($opp <= 0 || $opp === $caster) return;
    OnHealBase($caster, $opp, 1);
};
