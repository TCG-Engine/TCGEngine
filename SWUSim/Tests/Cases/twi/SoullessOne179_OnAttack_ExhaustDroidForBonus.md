# TWI_179 Soulless One (Unit 1/2, Space) — "On Attack: You may exhaust a friendly Droid unit or General
# Grievous. If you do, this unit gets +2/+0 for this attack." Attacking P2's base, exhausting the
# friendly Battle Droid gives Soulless One +2/+0 → deals 3.

## GIVEN
CommonSetup: yyk/grw/{myResources:0}
P1OnlyActions: true
WithP1SpaceArena: TWI_179:1:0
WithP1GroundArena: TWI_T01:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:EXHAUSTED
