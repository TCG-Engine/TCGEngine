# SHD_173 Guild Target on a UNIQUE host → deal 3 to a base instead of 2. Host is Synara San
# (unique 3/6 Grit), kept READY so her own exhausted-only bounty stays silent; starts at 2 damage
# so LAW_124's 4 defeats her (Grit counter 5). The 3-vs-2 value distinguishes the unique branch.

## GIVEN
CommonSetup: grw/grw
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SHD_033:1:2
WithP2GroundArenaUpgrade: 0:SHD_173

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirBase-0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:3
P1GROUNDARENAUNIT:0:DAMAGE:5
