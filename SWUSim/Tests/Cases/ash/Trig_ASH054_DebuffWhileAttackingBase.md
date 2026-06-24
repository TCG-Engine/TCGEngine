# ASH_054 Pointless to Resist (Upgrade) — Attached unit gets -3/-0 while attacking a base. P2's SEC_080
# (3/3) carries ASH_054 and attacks P1's base: its power is reduced to 0, so the base takes 0.
## GIVEN
CommonSetup: bbk/bbk
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:ASH_054
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
## WHEN
- P2>AttackGroundArena:0:BASE
## EXPECT
P1BASEDMG:0
