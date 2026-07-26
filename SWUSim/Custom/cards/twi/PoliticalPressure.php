<?php
// TWI_222
// Cost 1 - Political Pressure - [Cunning]
// Text: Choose an opponent. They may discard a random card from their hand. If they don't, create 2 Battle Droid tokens.

// TWI_222 Political Pressure — the opponent answered the "discard a random card?" YESNO. On YES,
// discard a random card from their hand; on NO (they don't), the CASTER creates 2 Battle Droids.
// $parts[0] = the caster's player number (the opponent is the one answering / $player here).
$customDQHandlers["TWI_222#0"] = function($player, $parts, $lastDecision) {
    $caster = intval($parts[0] ?? 0);
    $opp    = intval($player); // this handler runs under the opponent (the decider)
    if ($lastDecision === 'YES') {
        global $playerID;
        $playerID = $opp;
        $hand = ZoneSearch('myHand', null);
        if (!empty($hand)) {
            $pick = $hand[array_rand($hand)];
            DoDiscardCard($opp, $pick);
        } else {
            SWUCreateUnitTokens($caster, 'TWI_T01', 2); // no card to discard → they "don't" → droids
        }
    } else {
        SWUCreateUnitTokens($caster, 'TWI_T01', 2);
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_222:0"] = function($player, $mzID = '') {
// Political Pressure — "Choose an opponent. They may discard a random card
                          // from their hand. If they don't, create 2 Battle Droid tokens."
            $opp = OtherPlayer(intval($player));
            global $playerID;
            $playerID = $opp;
            $oppHand = ZoneSearch('myHand', null); // relative to $playerID = opponent
            if (empty($oppHand)) { SWUCreateUnitTokens(intval($player), 'TWI_T01', 2); return; }
            DecisionQueueController::AddDecision($opp, 'YESNO', '-', 1,
                tooltip: 'Discard_a_random_card_or_the_opponent_creates_2_Battle_Droids?');
            DecisionQueueController::AddDecision($opp, 'CUSTOM', 'TWI_222#0|' . intval($player), 1);
            return;
};
