<?php
// JTL_208
// Never Tell Me the Odds
// Text: Discard 3 cards from an opponent's deck and 3 cards from your deck. Deal damage to a unit equal to the number of cards with an odd cost discarded this way.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_208:0"] = function($player, $mzID = '') {
// Cunning — "Discard 3 cards from an opponent's deck and 3 cards from your deck.
                          // Deal damage to a unit equal to the number of cards with an odd cost discarded
                          // this way."
            global $playerID;
            $playerID = intval($player);
            $opp = OtherPlayer(intval($player));
            $odd = 0;
            for ($i = 0; $i < 3; $i++) { $c = SWUMillTopCard($opp);            if ($c !== null && (intval(CardCost($c)) % 2) === 1) $odd++; }
            for ($i = 0; $i < 3; $i++) { $c = SWUMillTopCard(intval($player)); if ($c !== null && (intval(CardCost($c)) % 2) === 1) $odd++; }
            if ($odd <= 0) return;
            SWUOfferUnitTarget(intval($player), '', [
                'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => $odd, 'side' => 'any',
                'prompt' => "Deal_{$odd}_damage_to_a_unit",
            ]);
            return;
};
