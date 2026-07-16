# ExpToUnderworld
#// LAW_249 Black Sun Cabalist (Villainy, cost 2) — When Played: give an Experience token to another
#// friendly Underworld unit. LAW_124 (Underworld) is the only other -> auto-target.

## GIVEN
CommonSetup: rrk/bgw/{myResources:2}
WithP1GroundArena: LAW_124:1:0
WithP1Hand: LAW_249

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_124
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
