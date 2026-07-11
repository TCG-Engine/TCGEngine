# SHD_150 Koska Reeves — with no upgrade attached, the "if this unit is upgraded" gate fails and there is
# no offer. The enemy SOR_046 is untouched and no decision is pending.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_150:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION
