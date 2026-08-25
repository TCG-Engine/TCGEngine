<?php
// HMW_154
// Cost 3 - Dooku's Solar Sailer - Droid Army Portent - [Aggression][Villainy] - Unit (Space) 3/3 -
// Traits: Separatist, Vehicle, Transport - Unique
// Text: When Played: If you control a unit that costs 1 or less, each opponent discards a card from
//       their hand.
//
// Two things decide this gate and both are easy to get wrong:
//   * COST IS ALWAYS THE PRINTED COST. A Token Unit's printed cost is 0, so it qualifies; a deployed
//     leader unit's is its leader card's printed cost (4+ in every set), so it never can. Reading a
//     "current"/discounted cost would be wrong on both counts.
//   * "you CONTROL" — GetUnitsInPlay is controller-scoped (a unit sits in its controller's arena), so a
//     stolen cheap unit counts for its new controller and an enemy's does not count at all.
// The Sailer itself is already in play when its own When Played resolves and costs 3, so it can never
// satisfy its own gate.
$whenPlayedAbilities["HMW_154:0"] = function($player, $mzID = '') {
    $player = intval($player);

    $qualifies = false;
    foreach (GetUnitsInPlay($player) as $u) {
        if (intval(CardCost($u->CardID ?? '')) <= 1) { $qualifies = true; break; }
    }
    if (!$qualifies) return;

    // "EACH opponent" — one call per live opponent, never a single OtherPlayer() call.
    // ⚠ This card shipped with exactly that bug on 2026-08-21 (and a comment reading "2-player: 'each
    //   opponent' is the single opponent", which is how the 2-seat assumption got written down as if it
    //   were a fact). At four seats only one opponent discarded.
    // SWUDiscardCards discards inline when a hand is at or below the count and otherwise queues the pick
    // on THAT seat's queue; nothing rides behind it here, so its queued-vs-inline return is not needed,
    // and each seat answers its own prompt independently. An empty hand is a clean no-op.
    foreach (OpponentsOf(intval($player)) as $opp) {
        SWUDiscardCards($player, 1, $opp);
    }
};
