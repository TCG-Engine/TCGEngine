# SOR_045 Yoda — "When Defeated: Choose any number of players. They each draw a card." Yoda attacks
# LAW_124 (4/7) and dies (2/4 takes 4). On defeat, choosing "You" → only P1 (Yoda's controller) draws.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_045:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Deck: SOR_095
WithP2Deck: SEC_080

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:You

## EXPECT
P1HANDCOUNT:1
P2HANDCOUNT:0
