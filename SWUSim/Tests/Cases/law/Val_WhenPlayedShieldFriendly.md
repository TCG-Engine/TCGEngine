# LAW_091 Val (2/4) — When Played: give a Shield token to another friendly unit. SOR_063 is the only
# other -> auto.

## GIVEN
CommonSetup: byk/bgw/{myResources:2}
WithP1GroundArena: SOR_063:1:0
WithP1Hand: LAW_091

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_063
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
