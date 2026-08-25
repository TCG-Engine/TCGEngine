<?php
// SHD_181
// Cost 4 - Pillage - [Aggression] - Event - Trait: Tactic
// Text: Choose a player. They discard 2 cards from their hand.

// ⚠ "Choose a PLAYER", not "an opponent" — YOU are a legal pick. That is why this is the first caller of
// SWUQueueChooseOpponent's $includeSelf, and why it is the one card in the Twin Suns sweep allowed to add
// a prompt to a 2-player game: with two seats there are genuinely two answers, and the old
// implementation (a bare SWUDiscardCards($player, 2), i.e. always the opponent) was missing that choice
// in Premier too — not only at four seats.
//
// ELIGIBILITY: only players who actually HOLD a card. Choosing an empty-handed player is a legal but
// null answer, so offering it is a fizzle-only option — and, more usefully, restricting the menu is what
// keeps the common case silent: when one player alone has cards the pick auto-resolves and no prompt is
// shown at all. That is how every pre-existing 2-player section here still passes untouched (their
// casters end with an empty hand, so the opponent is the only eligible seat).
//
// ⚠ THE CASTER'S OWN HAND EXCLUDES PILLAGE ITSELF. An event is already removed from hand (and sitting in
// the discard) by the time its When Played resolves, so the in-flight copy must not make its caster look
// eligible. Both the count below and SWUDiscardCards skip `removed` entries, so this is handled in one
// place each — do not re-add the card to the count "for symmetry".

// Cards $seat can actually discard right now (non-removed hand entries).
if (!function_exists('_SWUShd181HandCount')) {
    function _SWUShd181HandCount(int $seat): int {
        $n = 0;
        foreach (GetHand($seat) as $c) {
            if (empty($c->removed)) $n++;
        }
        return $n;
    }
}

$customDQHandlers["SHD_181#0"] = function ($player, $parts, $lastDecision) {
    $seat = SWUPickedOpponent($lastDecision);      // reads ANY seat token, the caster's own included
    if ($seat <= 0) return;
    SWUDiscardCards(intval($player), 2, $seat);    // the picked player discards 2 of their choice
};

$whenPlayedAbilities["SHD_181:0"] = function ($player, $mzID = '') {
    $me = intval($player);
    $eligible = [];
    foreach (GetLiveSeatsArray() as $seat) {
        if (_SWUShd181HandCount($seat) > 0) $eligible[] = $seat;
    }
    if (empty($eligible)) return;                  // nobody holds a card — nothing to choose between
    SWUQueueChooseOpponent($me, "SHD_181#0", "Which_player_discards_2_cards?", $eligible, true);
};
