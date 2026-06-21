# SEC_164 Warrior of Clan Ordo — decline the disclose → "if you don't" deals 2 to your own base.

## GIVEN
CommonSetup: rrw/grw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SEC_164:1:0
WithP1Hand: SEC_133

## WHEN
- P1>AttackGroundArena:0
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:3
P1BASEDMG:2
P1NODECISION
