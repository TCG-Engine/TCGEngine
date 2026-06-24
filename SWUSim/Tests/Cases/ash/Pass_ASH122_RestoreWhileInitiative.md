# ASH_122 Consortium StarViper (Space, 3/3) — While you have the initiative, this unit gains Restore 2.
# With P1 holding the initiative, the StarViper has Restore.
## GIVEN
CommonSetup: ggk/ggk
WithP1SpaceArena: ASH_122:1:0
WithActivePlayer: 1
WithInitiativePlayer: 1
WithInitiativeClaimed: true
## WHEN
- P1>Pass
## EXPECT
P1SPACEARENAUNIT:0:CARDID:ASH_122
P1SPACEARENAUNIT:0:HASKEYWORD:Restore
