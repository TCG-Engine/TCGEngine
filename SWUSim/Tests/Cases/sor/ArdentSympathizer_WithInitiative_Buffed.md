# SOR_161 Ardent Sympathizer (3/3) — "While you have the initiative, this unit
# gets +2/+0." P1 holds claimed initiative → reads 5/3.

## GIVEN
CommonSetup: rrw/rrw
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: SOR_161:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:3
