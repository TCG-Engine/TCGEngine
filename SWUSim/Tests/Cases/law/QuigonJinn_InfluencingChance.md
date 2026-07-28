# OnAttackLookTopDiscardOne
#// LAW_237 Qui-Gon Jinn (3/5, Sentinel) — When Played/On Attack: look at the top 3, you may discard 1,
#// put the rest back on top. Attacks the base; discard the top SOR_237.

## GIVEN
CommonSetup: yyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_237:1:0
WithP1Deck: SOR_237
WithP1Deck: SOR_046
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myDeck-0

## EXPECT
P1DECKCOUNT:2
P1DISCARDCOUNT:1

---

# OnAttackDiscardNothing
#// LAW_237 Qui-Gon Jinn — the discard is optional ("you may discard 1"). On Attack, P1 looks at the top
#// 3 and declines to discard: all 3 stay on the deck (put back on top), nothing is milled.

## GIVEN
CommonSetup: yyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_237:1:0
WithP1Deck: SOR_237
WithP1Deck: SOR_046
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P1DECKCOUNT:3
P1DISCARDCOUNT:0

---

# WhenPlayedLookTopDiscardOne
#// LAW_237 Qui-Gon Jinn — the same look-top-3/discard-1 fires When Played, not only On Attack. P1 plays
#// Qui-Gon (cost 4) from hand; the top card SOR_237 is discarded, the rest go back on top.

## GIVEN
CommonSetup: yyk/bgw/{myResources:4}
P1OnlyActions: true
WithP1Hand: LAW_237
WithP1Deck: SOR_237
WithP1Deck: SOR_046
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDeck-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_237
P1DECKCOUNT:2
P1DISCARDCOUNT:1

---

# FewerThanThreeCards
#// LAW_237 Qui-Gon Jinn — with fewer than 3 cards in deck, only the available cards are looked at. Deck
#// has a single card; On Attack, P1 discards it → deck empty, one card in discard.

## GIVEN
CommonSetup: yyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_237:1:0
WithP1Deck: SOR_237

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myDeck-0

## EXPECT
P1DECKCOUNT:0
P1DISCARDCOUNT:1

---

# EmptyDeckNoEffect
#// LAW_237 Qui-Gon Jinn — with an empty deck the look-at ability has nothing to reveal, so it resolves
#// with no effect and no decision. On Attack, deck stays empty and nothing is discarded.

## GIVEN
CommonSetup: yyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_237:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1DECKCOUNT:0
P1DISCARDCOUNT:0
P1NODECISION
