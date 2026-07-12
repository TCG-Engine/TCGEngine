# SHD_090 Maul (Unit, Ground, cost ?, 7/6, Ambush, Overwhelm, Force/Underworld)
#   "On Attack: You may choose another friendly Underworld unit. If you do, all combat damage that would be
#    dealt to this unit during this attack is dealt to the chosen unit instead."
# Maul (7 power) attacks SOR_181 Jabba (2/8, survives, deals 2 counter). P1 redirects the counter to the
# friendly Underworld unit LAW_124 (4/7): Maul takes 0, LAW_124 takes the 2, Jabba takes Maul's 7.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: [SHD_090:1:0 LAW_124:1:0]
WithP2GroundArena: SOR_181:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_090
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:CARDID:LAW_124
P1GROUNDARENAUNIT:1:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:7
