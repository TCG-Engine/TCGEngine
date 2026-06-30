# IBH_102 I Want Proof, Not Leads (reprint of IBH_074) — draw 2, discard 1. Confirms the duplicate.

## GIVEN
CommonSetup: rrk/bbw/{myResources:2}
P1OnlyActions: true
WithP1Hand: IBH_102
WithP1Deck: SOR_095
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-1

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:0
P1DISCARDCOUNT:2
P1NODECISION
