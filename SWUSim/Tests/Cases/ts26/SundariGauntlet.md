# OnDefenseDeals1OwnBase
#// TS26_024 Sundari Gauntlet (Unit 6/5 space, cost 5) — Sentinel + On Defense: deal 1 damage to your base.
#// When JTL_069 attacks Sundari, its On Defense deals 1 to P1's base.
## GIVEN
CommonSetup: grk/rrk
WithP1SpaceArena: TS26_024:1:0
WithP2SpaceArena: JTL_069:1:0
WithActivePlayer: 1
## WHEN
- P1>Pass
- P2>AttackSpaceArena:0:0
## EXPECT
P1BASEDMG:1
