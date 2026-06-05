# SOR_146 Zeb Orrelios — the deal-4 is optional ("you may"). Zeb defeats the defender, then DECLINES
# the may-choose (AnswerDecision:-), so the surviving ground unit takes no extra damage. Zeb still has
# the 3 combat damage from the defender.

## GIVEN
P1LeaderBase: SOR_011/SOR_025
P2LeaderBase: SOR_014/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_146:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:3
