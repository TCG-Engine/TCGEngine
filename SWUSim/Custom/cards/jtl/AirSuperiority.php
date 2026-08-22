<?php
// JTL_125
// Air Superiority
// Text: If you control more space units than an opponent, deal 4 damage to a ground unit that opponent controls.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_125:0"] = function($player, $mzID = '') {
// Air Superiority — "If you control more space units than an opponent, deal 4
                          // damage to a ground unit that opponent controls."
            global $playerID;
            $playerID = intval($player);
            // "If you control more space units than AN OPPONENT, deal 4 damage to a ground unit THAT
            // OPPONENT controls." ONE opponent is named and BOTH clauses hang off it.
            // ⚠ TWO defects above two seats, pointing OPPOSITE ways:
            //   (a) the COMPARISON asked one seat, so a caster who out-flies seat 3 but not seat 2 was
            //       simply told no;
            //   (b) the POOL was 'side'=>'their', which fans out across EVERY opponent — so you could
            //       satisfy the condition against seat 2 and then land the 4 on SEAT 4's unit. The pool
            //       GREW, so nothing looked broken.
            // ⚠ FILTER to opponents you actually out-fly: against anyone else the condition is false and
            //   the effect cannot happen at all, so they are a choice among nothing. (The event's cost is
            //   already paid on play, so this is not the fizzle-still-pays case.)
            $mine = 0; foreach (GetSpaceArena(intval($player)) as $u) { if (empty($u->removed)) $mine++; }
            $eligible = [];
            foreach (OpponentsOf(intval($player)) as $o) {
                $n = 0; foreach (GetSpaceArena($o) as $u) { if (empty($u->removed)) $n++; }
                if ($mine > $n) $eligible[] = $o;
            }
            if (empty($eligible)) return;
            SWUQueueChooseOpponent(intval($player), 'JTL_125#0',
                "Choose_an_opponent_you_out-fly", $eligible);
            return;
};

$customDQHandlers["JTL_125#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $opp = SWUPickedOpponent($lastDecision);
    if ($opp <= 0 || $opp === intval($player)) return;
    // 'ofSeat' scopes the damage to THAT opponent's ground units — the card says "that opponent",
    // not "an enemy".
    SWUOfferUnitTarget(intval($player), '', [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 4, 'side' => 'their', 'ofSeat' => $opp,
        'arena' => 'Ground',
        'prompt' => "Deal_4_to_a_ground_unit_that_opponent_controls",
    ]);
};
