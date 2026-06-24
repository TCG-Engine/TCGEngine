# ASH_125 Stolen Eta Shuttle (Space, 3/5, Hidden) — While you have the initiative, this unit gets +2/+0.
# With P1 holding the initiative, the Shuttle is at power 5.
## GIVEN
CommonSetup: ggk/ggk
WithP1SpaceArena: ASH_125:1:0
WithActivePlayer: 1
WithInitiativePlayer: 1
WithInitiativeClaimed: true
## WHEN
- P1>Pass
## EXPECT
P1SPACEARENAUNIT:0:CARDID:ASH_125
P1SPACEARENAUNIT:0:POWER:5
