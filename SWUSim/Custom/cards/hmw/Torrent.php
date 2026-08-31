<?php
// HMW_100
// Cost 2 - Torrent - [Vigilance] - Event - Traits: Disaster - NON-unique
// Text: Give a Weakness token to a unit. If you control a Naboo base, give 2 Weakness tokens to that
//       unit instead.
//
// "A UNIT" carries no controller word and no arena word, so the pool is EVERY unit on the board —
// friendly and enemy, ground and space (_SWUAllUnitsOnly), and with no "non-leader" it includes a
// DEPLOYED LEADER UNIT. Same reading as its set-mate HMW_071 Ravage, which is this card's closest twin:
// it is a Disaster, and pointing it at your own board is a legitimate if unusual play.
//
// The give is MANDATORY — no "you may", no "up to" — so it is a plain MZCHOOSE. With a single unit in
// play that auto-resolves through PASSPARAMETER, which is correct here (there is no decision to make);
// with none, the event fizzles cleanly and raises no prompt at all.
//
// "INSTEAD" MAKES THE NABOO CLAUSE A REPLACEMENT, NOT AN ADDITION — two tokens TOTAL, never one plus
// two. And "if YOU control" is the CASTER's base: an opposing Naboo base must not upgrade the count.
// The condition is read at RESOLUTION time (in the continuation), not when the offer is built, so a
// base that changes between the two is honoured as the rules would.
//
// Weakness (HMW_T02) is a -1/-1 TOKEN upgrade: it stacks (HMW_110 Emperor Palpatine gives two the same
// way, back-to-back), it CEASES with its host rather than going to a discard pile, and its -1 HP is HP
// REDUCTION — unpreventable, shield-independent, and lethal only through the state-based shrink sweep.
// Both tokens are attached BEFORE the single sweep, mirroring SWUGiveSplitWeakness: sweeping between
// them would compact the arena under a mzID that is about to be used again.
$whenPlayedAbilities["HMW_100:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $targets = _SWUAllUnitsOnly(intval($player));
    if (empty($targets)) return;   // no units in play: clean fizzle, no prompt
    SWUQueueChooseTarget(intval($player), $targets,
        "Give_a_Weakness_token_to_a_unit", "HMW_100#0");
};

$customDQHandlers["HMW_100#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    // _SWUControlsBaseWithTrait is the same helper HMW_240 Sandstorm uses for "while you control a
    // Tatooine base" — it reads GetBase($player), so the seat scoping ("YOU control") is inherent.
    $count = _SWUControlsBaseWithTrait(intval($player), 'Naboo') ? 2 : 1;
    for ($i = 0; $i < $count; $i++) {
        DoGiveTokenUpgrade(intval($player), $lastDecision, 'HMW_T02');
    }
    SWUCheckShrinkDefeats();   // -1 HP has no state-based defeat of its own
};
