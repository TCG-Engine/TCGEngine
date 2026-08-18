# EachPlayerDiscards
#// LAW_204 Every Day, More Lies (Aggression event, cost 1) — "Each player discards a card from their
#// hand." Caster has one extra card (auto-discards it); the opponent has two (real choose -> answers).

## GIVEN
CommonSetup: rrk/bgw/{myResources:1}
WithActivePlayer: 1
WithP1Hand: LAW_204
WithP1Hand: SEC_080
WithP2Hand: SOR_095
WithP2Hand: SOR_237

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myHand-0

## EXPECT
P1HANDCOUNT:0
P2HANDCOUNT:1
P1DISCARDCOUNT:2
P2DISCARDCOUNT:1

---

# CasterHasNoOtherCards
#// LAW_204 Every Day, More Lies — the caster can play it even holding no other cards. P1's hand is only the
#// event, so P1 has nothing to discard; the opponent (two cards) still discards one.

## GIVEN
CommonSetup: rrk/bgw/{myResources:1}
WithActivePlayer: 1
WithP1Hand: LAW_204
WithP2Hand: SOR_095
WithP2Hand: SOR_237

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myHand-0

## EXPECT
P1HANDCOUNT:0
P2HANDCOUNT:1
P1DISCARDCOUNT:1
P2DISCARDCOUNT:1

---

# NeitherPlayerHasOtherCards
#// LAW_204 Every Day, More Lies — resolves cleanly even when NEITHER player has another card to discard.
#// Only the event itself ends up in P1's discard; P2 discards nothing.

## GIVEN
CommonSetup: rrk/bgw/{myResources:1}
WithActivePlayer: 1
WithP1Hand: LAW_204

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P2HANDCOUNT:0
P1DISCARDCOUNT:1
P2DISCARDCOUNT:0

---

# OPPONENTChoosesTheirOwnDiscard_TheCasterCannotPickForThem
#// LAW_204 Every Day, More Lies — "EACH PLAYER discards a card from their hand", so the opponent picks
#// their own card from their own hand: the decision is raised on P2's queue and its pool is P2's two hand
#// cards, not P1's. The existing sections answer that decision with `myHand-0` from P2's seat without ever
#// asserting whose cards were on offer, which a discard driven from the caster's seat would also satisfy.

## GIVEN
CommonSetup: rrk/bgw/{myResources:1}
WithActivePlayer: 1
WithP1Hand: [LAW_204 SEC_080]
WithP2Hand: [SOR_095 SOR_237]

## WHEN
- P1>PlayHand:0

## EXPECT
P2SELECTABLEEXACT:myHand-0&myHand-1
P2HANDCOUNT:2
