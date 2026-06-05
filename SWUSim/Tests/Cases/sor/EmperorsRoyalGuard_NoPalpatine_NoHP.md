# SOR_082 Emperor's Royal Guard (3/4) — P1's leader is NOT Palpatine, so the
# +0/+1 does not apply. Reads its printed 3/4.
# (Absence guard — passes pre-implementation; stays meaningful once the buff exists.)

## GIVEN
P1LeaderBase: SOR_014/SOR_024
P2LeaderBase: SOR_014/SOR_024
SkipPreGame: true
WithP1GroundArena: SOR_082:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:4
