# SHD_117 Reputable Hunter (3-cost, Command) — "If an enemy unit has a Bounty, this unit costs 1 resource
# less to play." With the enemy Bounty unit SHD_095 in play, it costs 2 (3 resources → 1 left).

## GIVEN
CommonSetup: ggk/ggk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_117
WithP2GroundArena: SHD_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_117
P1RESAVAILABLE:1
