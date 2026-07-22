# AllUnitsLoseSentinel
#// ASH_040 Poe Dameron (Ground, 3/3) — "All units lose Sentinel." While Poe is in play, both players'
#// SOR_063 (innate Sentinel) lose Sentinel.
## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: ASH_040:1:0
WithP1GroundArena: SOR_063:1:0
WithP2GroundArena: SOR_063:1:0
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_063
P1GROUNDARENAUNIT:1:NOTKEYWORD:Sentinel
P2GROUNDARENAUNIT:0:CARDID:SOR_063
P2GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# EnemySentinelStripped_CanAttackBase
#// ASH_040 Poe Dameron — because ALL units lose Sentinel, the enemy SOR_063 (innate Sentinel) no longer
#// forces attacks onto itself, so P1's SOR_046 can attack the enemy base directly.
## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: ASH_040:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_063:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:1:BASE
## EXPECT
P2BASEDMG:3
