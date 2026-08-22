<?php
// LOF_065
// Cost 3 - Watto - No Money, No Parts, No Deal - [Vigilance] - Power 1 - HP 6
// Text: On Attack: An opponent chooses one: / You give an Experience token to a friendly unit. / You draw a card. /

// LOF_065 Watto — On Attack: an opponent chooses one: you give an Experience token to a friendly unit,
// OR you draw a card. The opponent picks; the caster gets the (chosen) benefit. Routed via an
// intermediate CUSTOM so the cross-player OPTIONCHOOSE survives the OnAttack $playerID restore.
$onAttackAbilities["LOF_065:0"] = function($player, $mzID) {
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LOF_065#0", 1);
};

$customDQHandlers["LOF_065#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    // "AN opponent chooses one" — Watto's controller picks WHICH opponent does the choosing.
    // ⚠ NO $eligible filter: the chosen opponent needs nothing on their board, in hand or in deck. They
    // are only being asked to pick between two things that happen to the CASTER, so no live opponent can
    // be filtered out as unable to act. (Shape 3 in the taxonomy, in its purest form — the "pool" the
    // chosen player acts on is not theirs at all.)
    SWUQueueChooseOpponent(intval($player), 'LOF_065#2|' . intval($player),
        "Choose_an_opponent_to_make_the_choice");
};

$customDQHandlers["LOF_065#2"] = function($player, $parts, $lastDecision) {
    $caster = intval($parts[0] ?? $player);
    $opp    = SWUPickedOpponent($lastDecision);
    if ($opp <= 0 || $opp === $caster) return;
    DecisionQueueController::AddDecision($opp, "OPTIONCHOOSE", "GiveExp&Draw", 1,
        tooltip: "Watto:_opponent_chooses_(you_give_an_Experience_or_you_draw)");
    DecisionQueueController::AddDecision($opp, "CUSTOM", "LOF_065#1|" . $caster, 1);
};

$customDQHandlers["LOF_065#1"] = function($player, $parts, $lastDecision) {
    $caster = intval($parts[0] ?? 0);
    global $playerID; $playerID = $caster;
    if ($lastDecision === 'Draw') { DoDrawCard($caster, 1); return; }
    // GiveExp → the caster gives an Experience token to a friendly unit.
    GiveTokenUpgrade($caster, '', [
        'prompt' => "Give_an_Experience_token_to_a_friendly_unit",
    ]);
};
