# SOR_088 Blizzard Assault AT-AT — the excess deal is "you may": declining leaves the other enemy
# unit untouched. Same setup as the deal-excess test, but the player declines → SOR_046 stays at 0
# damage (only the 3/3 it defeated is gone).

## GIVEN
P1LeaderBase: SOR_011/SOR_025
P2LeaderBase: SOR_014/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_088:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:0
