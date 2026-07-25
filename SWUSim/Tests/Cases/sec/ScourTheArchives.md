# SearchUpgradeDraw
#// SEC_072 Scour the Archives (event, cost 1) — Search the top 8 of your deck for an upgrade, reveal it,
#//   and draw it. The top of deck has one upgrade (SOR_069) among event fillers; P1 picks and draws it.

## GIVEN
CommonSetup: bbk/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: SEC_072
WithP1Deck: SOR_069
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_069

## EXPECT
P1HANDCOUNT:1
P1DISCARDCOUNT:1
P1DECKCOUNT:7

---

# TakeNothing_AllToBottom
#// SEC_072 Scour the Archives — the player may take nothing even when an upgrade is present. Declining
#//   ("take nothing") draws no card and places all 8 searched cards on the bottom of the deck. Deck
#//   stays at 8, hand is empty (SEC_072 played), and nothing is drawn.

## GIVEN
CommonSetup: bbk/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: SEC_072
WithP1Deck: SOR_069
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:8
P1DISCARDCOUNT:1
P1NODECISION
