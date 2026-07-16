# InitiativeBuff
#// SEC_108 Senator's Aide (Ground, 0/3) — "While you have the initiative, this unit gets +2/+0."
#//   P1 claims the initiative → SEC_108 becomes 2/3 for as long as P1 holds it.

## GIVEN
CommonSetup: ggk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_108:1:0

## WHEN
- P1>Claim

## EXPECT
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:3
