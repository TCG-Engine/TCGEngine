# SOR_161 Ardent Sympathizer (3/3) — P2 holds the initiative, so P1's Ardent
# Sympathizer does NOT get +2/+0. Reads its printed 3/3.
# (Absence guard — passes pre-implementation; stays meaningful once the buff exists.)

## GIVEN
CommonSetup: rrw/rrw
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: SOR_161:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3
