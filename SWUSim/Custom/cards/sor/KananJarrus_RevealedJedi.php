<?php
// SOR_047
// Cost 4 - Kanan Jarrus - Revealed Jedi - [Vigilance,Heroism] - Power 4 - HP 5
// Text: On Attack: You may discard 1 card from the defending player's deck for each friendly SPECTRE unit. Heal 1 damage from your base for each different aspect among the discarded cards.

// ── Deck-mill cards (Phase 8.2): SOR_047 Kanan, SOR_204 Greedo, SOR_188 Chopper ──
// SOR_047 Kanan Jarrus — "On Attack: You may discard 1 card from the defending player's deck for
// each friendly SPECTRE unit. Heal 1 damage from your base for each different aspect among the
// discarded cards." Optional whole effect → YESNO.
$onAttackAbilities["SOR_047:0"] = function($player, $mzID) {
    DecisionQueueController::AddDecision(intval($player), 'YESNO', '-', 1,
        'Discard_from_the_defender\'s_deck_per_Spectre,_then_heal_per_aspect?');
    DecisionQueueController::AddDecision(intval($player), 'CUSTOM', 'SOR_047#0', 1);
};

$customDQHandlers["SOR_047#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID;
    $playerID = intval($player);
    $defender = GetOpponent(intval($player)); // 2-player: the defending player is the opponent
    $spectre = 0;
    foreach (GetUnitsInPlay(intval($player)) as $u) {
        if (HasTrait($u->CardID, 'Spectre')) $spectre++;
    }
    if ($spectre <= 0) return;
    $aspects = [];
    for ($i = 0; $i < $spectre; $i++) {
        $milled = SWUMillTopCard($defender);
        if ($milled === null) break; // deck empty
        foreach (explode(',', CardAspect($milled) ?? '') as $a) {
            $a = trim($a);
            if ($a !== '') $aspects[$a] = true;
        }
    }
    $distinct = count($aspects);
    if ($distinct > 0) OnHealBase(intval($player), intval($player), $distinct);
};
