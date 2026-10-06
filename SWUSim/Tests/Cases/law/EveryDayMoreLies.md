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

---

# BothChooseIndependently_NothingDiscardedUntilBothHaveChosen
#// CR v9.0 7.1.a: "If an ability involves a choice made about hidden information (such as each player discarding a
#// card from their hand), each player makes their choice independently, and then all players resolve the ability
#// simultaneously." Both players have a real choice (P1's hand-0 is the event itself). P1 picks first — but while P2 is still choosing, P1's pick is
#// still in P1's HAND (nothing is public yet). Discarding P1's pick on the spot let P2 see it before choosing.
## GIVEN
CommonSetup: rrk/bgw/{myResources:1}
WithActivePlayer: 1
WithP1Hand: LAW_204
WithP1Hand: SEC_080
WithP1Hand: SOR_095
WithP2Hand: SOR_095
WithP2Hand: SOR_237
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-1
## EXPECT
P1HANDCOUNT:2
P1DISCARDCOUNT:1
P2HASDECISION

---

# BothChooseIndependently_ThenBothDiscardTogether
#// Same board, finished: once P2 has chosen too, both picks are discarded together.
## GIVEN
CommonSetup: rrk/bgw/{myResources:1}
WithActivePlayer: 1
WithP1Hand: LAW_204
WithP1Hand: SEC_080
WithP1Hand: SOR_095
WithP2Hand: SOR_095
WithP2Hand: SOR_237
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-1
- P2>AnswerDecision:myHand-1
## EXPECT
P1HANDCOUNT:1
P1DISCARDCOUNT:2
P2HANDCOUNT:1
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_237
