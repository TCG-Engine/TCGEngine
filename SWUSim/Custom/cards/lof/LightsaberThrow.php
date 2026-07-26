<?php
// LOF_176
// Cost 2 - Lightsaber Throw - [Aggression]
// Text: Discard a Lightsaber card from your hand. If you do, deal 4 damage to a ground unit and draw a card.

// LOF_176 Lightsaber Throw — discard the chosen Lightsaber from hand, draw a card, then deal 4 damage to
// a ground unit.
$customDQHandlers["LOF_176#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $cardID = $o->CardID;
    $o->removed = true;
    SWUAddToDiscard(intval($player), $cardID, 'HAND');
    DecisionQueueController::CleanupRemovedCards();
    DoDrawCard(intval($player), 1);
    $ground = SWUAllUnits(null, GroundArena);
    if (empty($ground)) return;
    SWUQueueChooseTarget(intval($player), $ground, "Deal_4_damage_to_a_ground_unit", "DEAL_UNIT_DAMAGE|4");
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_176:0"] = function($player, $mzID = '') {
// Lightsaber Throw — "Discard a Lightsaber card from your hand. If you do, deal 4
                          // damage to a ground unit and draw a card."
            global $playerID; $playerID = intval($player);
            $sabers = [];
            $hand = GetHand($player);
            for ($i = 0; $i < count($hand); $i++) {
                $c = $hand[$i];
                if (SWUObjGone($c)) continue;
                if (HasTrait($c->CardID ?? '', 'Lightsaber')) $sabers[] = "myHand-{$i}";
            }
            if (empty($sabers)) return;
            SWUQueueChooseTarget(intval($player), $sabers, "Discard_a_Lightsaber_(deal_4_to_a_ground_unit_+_draw)", "LOF_176#0");
            return;
};
