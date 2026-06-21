# SEC_155 Alexsandr Kallus — "While you have the initiative, each OTHER friendly unique unit gains Raid 2."
#   With Kallus in play and P1 holding initiative, the unique SEC_065 attacks the base for 4 + Raid 2 = 6.

## GIVEN
CommonSetup: rrw/rrk
WithActivePlayer: 1
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: SEC_155:1:0
WithP1GroundArena: SEC_065:1:0

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P2BASEDMG:6
P1NODECISION
