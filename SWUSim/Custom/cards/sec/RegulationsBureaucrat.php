<?php
// SEC_216
// Cost 2 - Regulations Bureaucrat - [Cunning] - Power 0 - HP 5
// Text: Action [Exhaust]: Exhaust a resource.

// SEC_216 Regulations Bureaucrat — Action [Exhaust]: exhaust a resource. Printed "a resource" (no
// friendly/enemy qualifier) → the controller chooses WHICH player's resource to exhaust (usually the
// opponent's for denial, but their own is a legal choice).
$unitAbilities["SEC_216"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", SWUPlayerPickerLabels(intval($player)), 1,
        tooltip: "Exhaust_a_resource_(choose_a_player)");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SEC_216#0|" . intval($player), 1);
};

$customDQHandlers["SEC_216#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $caster = intval($parts[0] ?? $player);
    $target = SWUDecodePlayerPick($lastDecision, $caster); // "You"→caster, "Opponent"→the other player
    SWUExhaustResources($target, 1, true); // exhaust one of the chosen player's ready resources (up to 1)
    SWUAfterAction($caster);
};
