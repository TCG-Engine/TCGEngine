# ASH_160 Kachirho Militia (Ground, 4/6, Hidden) — When an enemy ground unit attacks your base: ready
# this unit (once each round). P1's exhausted Kachirho readies when P2's SEC_080 attacks P1's base.
## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: ASH_160:0:0
WithP2GroundArena: SEC_080:1:0
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
## WHEN
- P2>AttackGroundArena:0:BASE
## EXPECT
P1BASEDMG:3
P1GROUNDARENAUNIT:0:CARDID:ASH_160
P1GROUNDARENAUNIT:0:READY
