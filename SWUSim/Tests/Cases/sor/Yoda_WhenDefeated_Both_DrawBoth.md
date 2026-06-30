# SOR_045 Yoda — "When Defeated: choose any number of players, they each draw." Choosing "Both" →
# both P1 and P2 draw a card.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_045:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Deck: SOR_095
WithP2Deck: SEC_080

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Both

## EXPECT
P1HANDCOUNT:1
P2HANDCOUNT:1
