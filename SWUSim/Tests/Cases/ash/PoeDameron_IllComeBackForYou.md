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

---

# CannotGainSentinelAgain
#// ASH_040 Poe Dameron — "All units lose Sentinel" is a continuous lock: a unit can't GAIN Sentinel back
#// while Poe is in play. P1 plays In the Heat of Battle (JTL_077, "each unit gains Sentinel for this
#// phase"), but Poe overrides it, so the enemy SOR_046 has no Sentinel and P1's other SOR_046 can attack
#// the enemy base directly.
## GIVEN
CommonSetup: brk/brk/{myResources:2;handCardIds:JTL_077}
WithP1GroundArena: ASH_040:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:1:BASE
## EXPECT
P2GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P2BASEDMG:3

---

# SpaceUnitsLoseSentinel
#// ASH_040 Poe Dameron — "All units lose Sentinel" applies across arenas, including space. With Poe in
#// play, both players' SHD_042 (Concord Dawn Interceptors, innate Sentinel) in the space arena lose it.
## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: ASH_040:1:0
WithP1SpaceArena: SHD_042:1:0
WithP2SpaceArena: SHD_042:1:0
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1SPACEARENAUNIT:0:CARDID:SHD_042
P1SPACEARENAUNIT:0:NOTKEYWORD:Sentinel
P2SPACEARENAUNIT:0:CARDID:SHD_042
P2SPACEARENAUNIT:0:NOTKEYWORD:Sentinel
