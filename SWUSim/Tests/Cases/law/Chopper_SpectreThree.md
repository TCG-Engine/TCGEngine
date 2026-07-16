# WhenPlayedTwoExpIfCunningVigilance
#// LAW_055 Chopper (1/2, Raid 1) — When Played: give an Experience token to this unit (2 instead if you
#// control a Cunning or Vigilance unit). P1 controls SOR_063 (Vigilance) -> 2 Experience (1/2 -> 3/4).

## GIVEN
CommonSetup: grw/bgw/{myResources:2}
WithP1GroundArena: SOR_063:1:0
WithP1Hand: LAW_055

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:LAW_055
P1GROUNDARENAUNIT:1:UPGRADECOUNT:2
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:1:HP:4
