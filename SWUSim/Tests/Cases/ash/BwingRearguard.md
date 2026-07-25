# SentinelWhileGroundUnit
#// ASH_078 B-Wing Rearguard (Space, 3/5) — While you control a ground unit, this unit gains Sentinel. With
#// a friendly ground unit (SOR_095) present, B-Wing has Sentinel.
## GIVEN
CommonSetup: bbw/bbk
WithP1SpaceArena: ASH_078:1:0
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1SPACEARENAUNIT:0:CARDID:ASH_078
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel

---

# NoGroundUnit_NoSentinel
#// ASH_078 B-Wing Rearguard — without a friendly ground unit, it does NOT have Sentinel.
## GIVEN
CommonSetup: bbw/bbk
WithP1SpaceArena: ASH_078:1:0
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1SPACEARENAUNIT:0:NOTKEYWORD:Sentinel

---

# ControlledForeignGroundUnit_GrantsSentinel
#// ASH_078 B-Wing Rearguard — "While you control a ground unit" keys off CONTROL, not ownership. A ground
#// unit P1 controls but P2 owns still satisfies the condition, so B-Wing has Sentinel.
## GIVEN
CommonSetup: bbw/bbk
WithP1SpaceArena: ASH_078:1:0
WithP1GroundArenaControlled: SOR_095:2
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1SPACEARENAUNIT:0:CARDID:ASH_078
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel

---

# OwnedButEnemyControlledGroundUnit_NoSentinel
#// ASH_078 B-Wing Rearguard — a ground unit P1 OWNS but P2 CONTROLS does not count for P1, so B-Wing
#// does NOT have Sentinel (control, not ownership, matters).
## GIVEN
CommonSetup: bbw/bbk
WithP1SpaceArena: ASH_078:1:0
WithP2GroundArenaControlled: SOR_095:1
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1SPACEARENAUNIT:0:CARDID:ASH_078
P1SPACEARENAUNIT:0:NOTKEYWORD:Sentinel

---

# BlueLeaderMovedToGround_GrantsSentinel
#// ASH_078 B-Wing Rearguard — the "while you control a ground unit" aura recomputes when arena membership
#// changes. P1 plays Blue Leader (JTL_096) and pays 2 to move it from space to the ground arena; the moved
#// unit now satisfies the condition, so B-Wing (still in space) gains Sentinel.
## GIVEN
CommonSetup: ggw/rrk/{myResources:8;handCardIds:JTL_096}
WithP1SpaceArena: ASH_078:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_096
P1SPACEARENAUNIT:0:CARDID:ASH_078
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel
