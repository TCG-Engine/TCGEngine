<?php
// JTL_121
// Cost 0 - Salvage - [Command]
// Text: Play a Vehicle unit from your discard pile (paying its cost). Then, deal 1 damage to it.

// ── JTL_121 Salvage — play the chosen Vehicle unit from the discard at cost, then deal 1 damage to it.
$customDQHandlers["JTL_121#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    SWUNestedPlay(intval($player), $lastDecision);   // nested: outer event owns the after-action // full cost via canonical play
    $newMz = $GLOBALS['gLastPlayedMzID'];
    if ($newMz === '') return;
    SWUDealDamageToUnit($newMz, 1, intval($player));
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_121:0"] = function($player, $mzID = '') {
// Salvage — "Play a Vehicle unit from your discard pile (paying its cost). Then,
                          // deal 1 damage to it." Offer the AFFORDABLE Vehicle units in the discard; the
                          // continuation plays the pick at cost and deals 1 to it.
            global $playerID;
            $playerID = intval($player);
            $targets = [];
            $myD = GetDiscard(intval($player));
            for ($i = 0; $i < count($myD); $i++) {
                $e = $myD[$i];
                if (SWUObjGone($e)) continue;
                $cid = $e->CardID ?? '';
                if ($cid === '' || strpos(CardType($cid) ?? '', 'Unit') === false) continue; // a unit
                if (!HasTrait($cid, 'Vehicle')) continue;                                      // ... a Vehicle
                $cost = max(0, intval(CardCost($cid)) + SWUAspectPenalty(intval($player), $cid));
                if (SWUTotalPaymentCapacity(intval($player)) < $cost) continue;                 // affordable only
                $targets[] = "myDiscard-$i";
            }
            if (empty($targets)) return; // no affordable Vehicle in the discard → fizzle
            SWUQueueChooseTarget(intval($player), $targets,
                "Play_a_Vehicle_unit_from_your_discard_(paying_its_cost)", "JTL_121#0");
            return;
};
