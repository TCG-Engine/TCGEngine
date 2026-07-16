# DealAndBuff
#// LOF_138 Sith Holocron — attached gains "On Attack: may deal 2 to a friendly unit. If you do, this unit
#// gets +2/+0 for this attack." Plo Koon (6 + 1 from the +1/+1 Holocron = 7) attacks the base, deals 2 to
#// the friendly SOR_046, and gets +2 → deals 9 to the base.

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP1GroundArenaUpgrade: 0:LOF_138
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:2
P2BASEDMG:9
