# TWI_234 The Invisible Hand — "On Attack: Exhaust any number of friendly Separatist units. Deal 1
# damage to the defending player's base for each unit exhausted this way." Invisible Hand (ready)
# attacks P2's base; On Attack offers the 2 ready friendly Battle Droid tokens (Separatist) to exhaust.
# Choosing both exhausts them and deals 2 to P2's base — on top of Invisible Hand's own 4 attack damage
# → P2 base takes 6 total.

## GIVEN
CommonSetup: gyk/grw/{myResources:0}
P1OnlyActions: true
WithP1SpaceArena: TWI_234:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P2BASEDMG:6
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:EXHAUSTED
