# SEC_254 Heroic ARC-170 — no damaged friendly unit → no damage offered.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_254

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION
