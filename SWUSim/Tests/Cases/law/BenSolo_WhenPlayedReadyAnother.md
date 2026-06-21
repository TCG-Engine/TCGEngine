# LAW_185 Ben Solo (8/8, Hidden) — When Played/When Defeated: ready another friendly unit; it can't be
# attacked this phase. Ready the exhausted SEC_080.

## GIVEN
CommonSetup: rrw/bgw/{myResources:9}
WithP1GroundArena: SEC_080:0:0
WithP1Hand: LAW_185

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:READY
