# SHD_046 Rey — the Shield rider only fires for a NON-Heroism target. Healing the friendly SOR_046 (Heroism)
# clears its 2 damage but grants no Shield.

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SHD_046:1:0
WithP1GroundArena: SOR_046:1:2

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:DAMAGE:0
P1GROUNDARENAUNIT:1:SHIELDCOUNT:0
