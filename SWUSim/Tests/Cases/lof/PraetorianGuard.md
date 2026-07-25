# 4Power_Sentinel
#// LOF_085 Praetorian Guard (2/5) — "While you control a unit with 4 or more power, this unit gains
#// Sentinel." With the 4-power LAW_124 controlled, it has Sentinel.

## GIVEN
CommonSetup: rrk/ggw
WithP1GroundArena: LOF_085:1:0
WithP1GroundArena: LAW_124:1:0

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# NoHighPower_NoSentinel
#// LOF_085 Praetorian Guard (2/5) — negative: with no friendly unit at 4+ power (only itself at 2), it
#// does not have Sentinel.

## GIVEN
CommonSetup: rrk/ggw
WithP1GroundArena: LOF_085:1:0

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# UpgradeGives4Power_Sentinel
#// LOF_085 Praetorian Guard — the 4-power unit can reach 4 via an upgrade. A 3/3 Battlefield Marine
#// (SOR_095) with an Experience token (SOR_T01, +1/+1) becomes 4/4, so Praetorian gains Sentinel. Ref:
#// "gains Sentinel while you control a unit with 4 or more power (with upgrades)".

## GIVEN
CommonSetup: rrk/ggw
WithP1GroundArena: LOF_085:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 1:SOR_T01

## EXPECT
P1GROUNDARENAUNIT:1:POWER:4
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# SpaceUnit4Power_Sentinel
#// LOF_085 Praetorian Guard — the 4-power unit can be in a DIFFERENT arena; the check is arena-agnostic. A
#// 7/7 Home One (SOR_102) in space grants the ground Praetorian Sentinel. Ref: "gains Sentinel while you
#// control a unit with 4 or more power" (Home One in space arena).

## GIVEN
CommonSetup: rrk/ggw
WithP1GroundArena: LOF_085:1:0
WithP1SpaceArena: SOR_102:1:0

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# SelfReaches4Power_Sentinel
#// LOF_085 Praetorian Guard — Praetorian itself can satisfy the condition. Entrenched (SOR_072, +3/+3)
#// raises the 2/5 Praetorian to 5/8 (5 power ≥ 4), so it gains Sentinel from its own power. Ref: "gains
#// Sentinel while he has 4 power or more".

## GIVEN
CommonSetup: rrk/ggw
WithP1GroundArena: LOF_085:1:0
WithP1GroundArenaUpgrade: 0:SOR_072

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
