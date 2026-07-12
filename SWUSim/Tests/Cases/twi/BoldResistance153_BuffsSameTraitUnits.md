# TWI_153 Bold Resistance (Event, cost 3, Aggression/Heroism) — "Choose up to 3 units that share the
# same Trait. Each of those units gets +2/+0 for this phase." Three Battle Droid tokens (all share
# Droid/Trooper/Separatist) each become 3 power.

## GIVEN
CommonSetup: rrw/grw/{myResources:3;handCardIds:TWI_153}
P1OnlyActions: true
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1&myGroundArena-2

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:2:POWER:3
