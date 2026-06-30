# SEC_132 Imperial Occupier (Ground, 2/2, Aggression/Villainy) — When Defeated: create a Spy token.
# SEC_132 attacks LAW_124 (4/7) and dies → its When Defeated creates a Spy.

## GIVEN
CommonSetup: rrk/grw
P1OnlyActions: true
WithP1GroundArena: SEC_132:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_T01
P1DISCARDCOUNT:1
P1NODECISION
