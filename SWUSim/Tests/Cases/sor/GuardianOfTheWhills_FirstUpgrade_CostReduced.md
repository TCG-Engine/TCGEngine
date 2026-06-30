# SOR_061 Guardian of the Whills (Unit 2/2, Vigilance) — "The first upgrade you play on this unit each
# round costs 1 less." The Guardian is the only friendly unit, so SOR_069 Resilient (+0/+3, Vigilance,
# cost 1) auto-attaches to it and the discount makes it cost 0: 3 ready resources → 3 left. The host
# becomes 2/5 with one upgrade.

## GIVEN
CommonSetup: bbk/bbk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_061:1:0
WithP1Hand: SOR_069

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:HP:5
P1RESAVAILABLE:3
