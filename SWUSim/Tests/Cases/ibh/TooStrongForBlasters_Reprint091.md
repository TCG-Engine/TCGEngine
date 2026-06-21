# IBH_091 Too Strong for Blasters (reprint of IBH_066) — heal 2 from a unit. Confirms the duplicate.

## GIVEN
CommonSetup: bbw/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: IBH_091
WithP1GroundArena: SOR_046:1:3

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1
P1NODECISION
