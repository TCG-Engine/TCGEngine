# BaseDamageCappedAtFour
#// ASH_070 At Attin Safety Droid (Ground, 1/4, cost 2) — "If your base would be dealt more than 4 damage,
#// prevent all but 4 of that damage." P1 controls the Droid; P2's SOR_038 (7 power) attacks P1's base, so
#// the 7 combat damage is capped to 4.
## GIVEN
CommonSetup: bbw/bbk
WithP1GroundArena: ASH_070:1:0
WithP2GroundArena: SOR_038:1:0
## WHEN
- P1>Pass
- P2>AttackGroundArena:0:BASE
## EXPECT
P1BASEDMG:4

---

# FourOrLessUnaffected
#// ASH_070 At Attin Safety Droid — the cap only triggers above 4. P2's SOR_095 (3 power) attacks P1's base
#// while the Droid is in play; 3 ≤ 4, so the full 3 lands (the cap does not reduce it).
## GIVEN
CommonSetup: bbw/bbk
WithP1GroundArena: ASH_070:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>Pass
- P2>AttackGroundArena:0:BASE
## EXPECT
P1BASEDMG:3

---

# OpponentBaseUncapped
#// ASH_070 At Attin Safety Droid — the cap protects only ITS controller's base. P1's Droid does nothing for
#// P2's base: P1's SOR_038 (5 power) attacks P2's base for the full 5 (> 4, uncapped).
## GIVEN
CommonSetup: bbw/bbk
WithP1GroundArena: ASH_070:1:0
WithP1GroundArena: SOR_038:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:1:BASE
## EXPECT
P2BASEDMG:5

---

# UnitDamageUncapped
#// ASH_070 At Attin Safety Droid — the cap is for BASE damage only. P2's SOR_038 (5 power) attacks P1's
#// SOR_046 (7 HP); the 5 combat damage is not capped to 4 — SOR_046 takes the full 5.
## GIVEN
CommonSetup: bbw/bbk
WithP1GroundArena: ASH_070:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_038:1:0
## WHEN
- P1>Pass
- P2>AttackGroundArena:0:1
## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:5
