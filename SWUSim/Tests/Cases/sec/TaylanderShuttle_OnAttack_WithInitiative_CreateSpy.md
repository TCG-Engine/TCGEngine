# SEC_115 Taylander Shuttle (Space, 2/4, Command) — On Attack: if you have the initiative, create a Spy.
# P1 holds claimed initiative → attacking the base creates a Spy token.

## GIVEN
CommonSetup: ggw/rrk
WithActivePlayer: 1
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1SpaceArena: SEC_115:1:0

## WHEN
- P1>AttackSpaceArena:0

## EXPECT
P2BASEDMG:2
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_T01
P1NODECISION
