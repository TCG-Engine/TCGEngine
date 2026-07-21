# StatOverride
#// LOF_056 Size Matters Not — "Attached unit's printed power is considered to be 5 and its printed HP is
#// considered to be 5." On Plo Koon (6/8) the override makes him exactly 5/5.

## GIVEN
CommonSetup: bbw/rrk
WithP1GroundArena: LOF_050:1:0
WithP1GroundArenaUpgrade: 0:LOF_056

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5

---

# StatOverride_RaisesLowStatUnit
#// LOF_056 Size Matters Not — the printed 5/5 override RAISES a low-stat unit as well as lowering a big one.
#// Death Star Stormtrooper (SOR_128, printed 3/1) becomes exactly 5/5. Ref: "should change printed
#// stats to 5/5 for a low-stat unit".

## GIVEN
CommonSetup: bbw/rrk
WithP1GroundArena: SOR_128:1:0
WithP1GroundArenaUpgrade: 0:LOF_056

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5

---

# ForceUnitDiscount
#// LOF_056 Size Matters Not (cost 3) — "If you control a Force unit, this upgrade costs 1 resource less."
#// P1 controls Plo Koon (LOF_050, Force) and plays Size Matters Not onto him for 2, leaving 4 of 6 resources.
#// Ref: "should cost one less if you control a Force unit".

## GIVEN
CommonSetup: bbw/rrk/{myResources:6;handCardIds:LOF_056}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:4
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# NoForceUnit_FullCost
#// LOF_056 Size Matters Not — without a Force unit the discount does not apply: played onto Battlefield Marine
#// (SOR_095, non-Force) it costs the full 3, leaving 3 of 6 resources. Baseline contrast to the Force-unit
#// discount case.

## GIVEN
CommonSetup: bbw/rrk/{myResources:6;handCardIds:LOF_056}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:3
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
