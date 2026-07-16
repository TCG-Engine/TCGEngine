# NoSentinelWithoutEnemyUpgrade
#// SHD_052 Sugi — no enemy upgrade (own-side upgrades don't count) → no Sentinel.

## GIVEN
CommonSetup: bbw/bbw
WithP1GroundArena: SHD_052:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 1:SOR_120
WithP2GroundArena: SOR_095:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# SentinelWhileEnemyUpgraded
#// SHD_052 Sugi — "While an enemy unit is upgraded, this unit gains Sentinel." Guard test for the
#// existing HasConditionalKeyword_Sentinel case (implemented, previously untested). Enemy marine
#// wears an upgrade → Sugi has Sentinel.

## GIVEN
CommonSetup: bbw/bbw
WithP1GroundArena: SHD_052:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
