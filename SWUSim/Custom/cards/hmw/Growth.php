<?php
// HMW_272
// Cost 5 - Growth - [no aspect] - Event - Trait: Innate
// Text: Create a Beast token.
//       Heal 3 damage from your base.
//       Draw a card.

// HMW_272 Growth — THREE INDEPENDENT CLAUSES. They are separate sentences joined by full stops, not by
// "If you do" and not by "Then", so none of them gates any other: each resolves as much of itself as it
// can and the rest carry on regardless. Concretely, and each pinned by its own section in
// Tests/Cases/hmw/Growth.md: a base that can't be healed (TWI_132 Confederate Tri-Fighter) or is already
// undamaged does not cost you the Beast or the card; an EMPTY DECK (which deals 3 to your base instead
// of drawing, CR 6.1) does not cost you the heal. The single early `return` that is the natural way to
// write "nothing to heal" / "nothing to draw" is exactly what those sections exist to catch.
//
// Order is as printed — create, heal, draw — and it is observable: with an empty deck the heal must
// clamp the base to 0 BEFORE the deck-out damage puts 3 back on.
$whenPlayedAbilities["HMW_272:0"] = function($player, $mzID = '') {
    global $playerID;
    $playerID = intval($player);
    $me = intval($player);

    // ── Clause 1 — "Create a Beast token." HMW_T03, a 3/3 Ground Token Unit (trait Creature). Routed
    // through SWUCreateUnitToken rather than seating the token by hand so the shared creation ceremony
    // applies: entry keywords, the enters-play observers, and — the one that is easy to lose — ASH_094
    // Moff Jerjerrod's "create twice that number instead" replacement, which the helper offers as a
    // YESNO. There is no rider on the created token here, so the plain single-token call is right;
    // a "create a token AND give it X" card must use SWUCreateUnitTokens($upgradeToken) instead, or
    // Jerjerrod's extra token arrives bare.
    SWUCreateUnitToken($me, 'HMW_T03');

    // ── Clause 2 — "Heal 3 damage from YOUR base." Both args are the caster: healing is seat-scoped and
    // the opponent's base is never touched. OnHealBase clamps at 0 (no negative damage) and is also
    // where the "bases can't be healed" locks (SOR_160 Wolffe, TWI_132) are enforced — when one is
    // active this is a clean no-op, which is correct here precisely because nothing is gated on it.
    //
    // ⚠ This and the draw below run INLINE, i.e. ahead of the Jerjerrod YESNO clause 1 may have queued.
    // Deliberate and unobservable: neither reads the arena, and Jerjerrod's handler reads neither the
    // base nor the hand. Do not "fix" it by deferring them behind the decision.
    OnHealBase($me, $me, 3);

    // ── Clause 3 — "Draw a card." Unconditional: on an empty deck DoDrawCard applies the CR 6.1
    // deck-out damage instead, which is a resolution of this clause, not a failure of the ability.
    DoDrawCard($me, 1);
};
