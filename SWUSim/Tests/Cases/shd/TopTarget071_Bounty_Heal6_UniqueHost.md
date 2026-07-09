# SHD_071 Top Target on a UNIQUE host → heal 6 instead of 4. Host is Synara San (SHD_033, unique
# 3/6 Grit), kept READY so her own exhausted-only bounty does NOT trigger — only Top Target's is
# offered. She starts at 2 damage; LAW_124's 4 damage defeats her (Grit counter 3+2=5 onto the
# attacker). P1's base starts at 6 → heals 6 → 0.

## GIVEN
CommonSetup: grw/grw/{myBaseDamage:6}
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SHD_033:1:2
WithP2GroundArenaUpgrade: 0:SHD_071

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myBase-0

## EXPECT
P2GROUNDARENACOUNT:0
P1BASEDMG:0
P1GROUNDARENAUNIT:0:DAMAGE:5
