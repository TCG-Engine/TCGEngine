# SHD_216 Chain Code Collector — against a NON-Bounty defender (SOR_046) there is no debuff, so its full
# 3 counter-power kills the fragile Collector (2 HP), and SOR_046 survives with 4 damage.

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1GroundArena: SHD_216:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:4
