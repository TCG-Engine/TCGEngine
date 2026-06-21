# LAW_059 Highsinger (4/2) — When Played: give an Experience token to another friendly Command unit.
# SOR_095 (Command,Heroism) is the only one -> auto.

## GIVEN
CommonSetup: grk/bgw/{myResources:3}
WithP1GroundArena: SOR_095:1:0
WithP1Hand: LAW_059

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
