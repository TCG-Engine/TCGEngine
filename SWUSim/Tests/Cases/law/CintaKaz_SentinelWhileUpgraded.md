# LAW_105 Cinta Kaz (3/5) — While this unit is upgraded, she gains Sentinel. With SOR_120 attached she
# has Sentinel.

## GIVEN
CommonSetup: bbw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_105:1:0
WithP1GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_105
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
