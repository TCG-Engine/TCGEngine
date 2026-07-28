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
    $opp = OtherPlayer(intval($player));
    DecisionQueueController::AddDecision($opp, "OPTIONCHOOSE", "GiveExp&Draw", 1,
        tooltip: "Watto:_opponent_chooses_(you_give_an_Experience_or_you_draw)");
    DecisionQueueController::AddDecision($opp, "CUSTOM", "LOF_065#1|" . intval($player), 1);
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
