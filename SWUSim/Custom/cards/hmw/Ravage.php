<?php
// HMW_071
// Cost 4 - Ravage - [Vigilance][Villainy] - Event - Traits: Disaster, Tactic - NON-unique
// Text: Distribute up to 3 Weakness tokens among any number of units.
//
// "any number of UNITS" carries no controller word and no arena word, so the pool is EVERY unit on the
// board — friendly and enemy, ground and space (_SWUAllUnitsOnly). It is a Disaster; hitting your own
// units is a legitimate, if unusual, play. It also carries no per-unit limit: "any number of units"
// includes exactly one, so all 3 may land on a single body.
//
// "UP TO 3" ⇒ the UPTO flag, which lets the player assign fewer — and an assignment of ZERO is how the
// soft pass is expressed for this wording (standing ruling: "up to N" has no declinable target; the
// decline IS an amount of zero). The event is still paid for and still discarded either way.
//
// Weakness (HMW_T02) is a -1/-1 TOKEN upgrade: it stacks, it CEASES with its host rather than going to
// a discard pile, and its -1 HP is HP REDUCTION — unpreventable, shield-independent, and lethal only
// through the state-based shrink sweep. SWUGiveSplitWeakness applies every token before sweeping once,
// so two 1-HP units named in one assignment both die (a per-target sweep would compact the arena and
// strand the later mzIDs).
$whenPlayedAbilities["HMW_071:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $targets = _SWUAllUnitsOnly(intval($player));
    if (empty($targets)) return;   // no units in play: clean fizzle, no prompt
    SWUQueueDistributeWeakness(intval($player), 3, $targets, true,
        "Distribute_up_to_3_Weakness_tokens_among_any_number_of_units");
};
