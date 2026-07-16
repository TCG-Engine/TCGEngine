# AttackBountyHunterBuff
#// LAW_157 Target Tagger (Command, cost 3) — When Played: you may attack with a unit. If it's a Bounty
#// Hunter, it gets +2/+0 for this attack. LAW_124 (Bounty Hunter, power 4) attacks the base for 4+2 = 6.

## GIVEN
CommonSetup: ggw/bgw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP1Hand: LAW_157

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:6
