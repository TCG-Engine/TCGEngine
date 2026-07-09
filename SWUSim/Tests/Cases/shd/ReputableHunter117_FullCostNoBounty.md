# SHD_117 Reputable Hunter — without an enemy Bounty unit it costs the full 3 (3 resources → 0 left).

## GIVEN
CommonSetup: ggk/ggk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_117
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_117
P1RESAVAILABLE:0
