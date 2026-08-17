<?php
// HMW_123
// Cost 6 - King Grakchawwaa - King of Kashyyyk - [Command,Heroism] - Unit (Ground) 6/6
// Traits: Wookiee, Official
// Text: When Played: For each other friendly Wookiee unit, resource the top card of your deck.
//       Ready each card resourced this way.

$whenPlayedAbilities["HMW_123:0"] = function($player, $mzID = '') {
    global $playerID;
    $playerID = intval($player);
    $me = intval($player);

    // COUNT — "for each OTHER friendly Wookiee unit".
    // - "other" excludes the King himself by UniqueID: he IS a Wookiee, so without this he would always
    //   resource one extra.
    // - "friendly" = units the CASTER controls; GetUnitsInPlay is already controller-scoped.
    // - A DEPLOYED LEADER is a unit, and GetUnitsInPlay returns it, so a Wookiee leader counts.
    // - TraitContains (not bare-CardID HasTrait) so a GRANTED Wookiee trait counts and a per-instance
    //   trait loss is honoured.
    $self = intval((GetZoneObject($mzID)->UniqueID ?? 0));
    $n = 0;
    foreach (GetUnitsInPlay($me) as $u) {
        if (SWUObjGone($u)) continue;
        if (intval($u->UniqueID ?? 0) === $self) continue;
        if (TraitContains($u, 'Wookiee')) $n++;
    }
    if ($n <= 0) return;

    // RESOURCE + READY. Bounded by the deck, so a short deck resources only what is there and an empty
    // deck resources nothing — and NOTHING here routes through a draw, so the CR 6.1 empty-deck base
    // damage must not fire.
    // The per-iteration deck re-read is DEFENSIVE, not proven necessary: mutation-testing showed that
    // hoisting the read out of the loop and indexing $deck[$i] also passes every section today. It is
    // kept because it stays correct if anything else ever removes from the deck mid-effect (the shape
    // that actually bit LAW_171 Stockpile), but do not describe it as load-bearing.
    // Status 1 = READY, which is the whole point of the rider: a normal "resource a card" enters
    // EXHAUSTED (Status 0), as in LAW_083 Broken Horn.
    for ($i = 0; $i < $n; $i++) {
        DecisionQueueController::CleanupRemovedCards();
        $playerID = $me;
        $deck = ZoneSearch("myDeck", null);
        if (empty($deck)) break;
        $r = MZMove($me, $deck[0], "myResources");
        if ($r === null) break;
        $r->Status     = 1;    // "Ready each card resourced this way"
        $r->Owner      = $me;
        $r->Controller = $me;
    }
    SWUKeepCreditTokensLast($me);
};
