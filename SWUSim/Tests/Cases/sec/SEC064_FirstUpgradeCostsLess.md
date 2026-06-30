# SEC_064 Congress of Malastare (Ground, 5/5) — "The first upgrade you play each phase costs 1 resource
#   less." P1 plays SOR_120 (cost 2) onto SEC_064; with the discount it costs 1, leaving 1 of 2 resources.

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SEC_064:1:0
WithP1Hand: SOR_120

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
