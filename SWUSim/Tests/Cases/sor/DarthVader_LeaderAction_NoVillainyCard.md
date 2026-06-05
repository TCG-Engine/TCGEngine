# SOR_010 Darth Vader — Leader Action: No Villainy card played → exhaust + spend resource, no damage.

## GIVEN
CommonSetup: rrk/grw/{myResources:1}
WithP2GroundArena: SOR_095:2:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:0
P1LEADER:EXHAUSTED
P1RESCOUNT:1
P1RESAVAILABLE:0