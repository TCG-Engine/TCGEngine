# ASH_079 Koska Reeves (Ground, 4/4) — When Played: if a friendly unit was defeated this phase, create a
# Mandalorian token. P1's Stormtrooper dies attacking first (sets the phase flag); then ASH_079 is played
# → a Mandalorian token is created. The created token (a Token Unit) ALSO turns on Koska's "while you
# control a token unit, gain Sentinel" passive.

## GIVEN
CommonSetup: yrw/grw/{myResources:9;handCardIds:ASH_079}
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:ASH_079
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:1:CARDID:ASH_T01
