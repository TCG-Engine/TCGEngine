# DebuffWhileAttackingBase
#// ASH_054 Pointless to Resist (Upgrade) — Attached unit gets -3/-0 while attacking a base. P2's SEC_080
#// (3/3) carries ASH_054 and attacks P1's base: its power is reduced to 0, so the base takes 0.
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

---

# AttackingUnit_NoPenalty
#// ASH_054 Pointless to Resist — the -3/-0 applies only while attacking a BASE. Attacking a UNIT, the host
#// SOR_046 keeps its full 3 power and defeats SEC_080 (3/3).
## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_054
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENACOUNT:0
