# Damaged_GetsPlusFourPlusZero
#// IC27_162 Chewbacca — Cool Backpack. Unit (Ground), cost 6, [Cunning][Heroism], 4/6, Rebel/Wookiee, unique.
#// "Sentinel. While this unit is damaged, he gets +4/+0."
#// LOF_233 Scimitar's shape (+3/+0 while damaged), read live in ObjectCurrentPower: one damage counter is enough,
#// and the bonus is power only. Sentinel is the generated keyword (no card code).
#//
#// COVERAGE: offer=N/A (a self-referential static — nothing is chosen) · decline=N/A (no "you may") ·
#//           boundary=Damaged_GetsPlusFourPlusZero (1 damage → 8) vs Undamaged_IsFour (0 → 4) ·
#//           ends=HealedToZero_BonusEnds · shields=ShieldAbsorbsTheHit_NotDamaged_NoBonus ·
#//           blank=Blanked_NoBonus · combat=Damaged_AttacksForEight ·
#//           control=N/A (the condition and the bonus are both on this unit — controller-independent) ·
#//           reqboundary=TakesDamage_ThenRequestBoundary_StillEight (nothing stored; recomputed on every read) ·
#//           modes=2P only (no player reference, no "friendly"/"enemy")

## GIVEN
CommonSetup: yyw/rrk
SkipPreGame: true
WithP1GroundArena: IC27_162:1:1

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:IC27_162
P1GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:POWER:8
P1GROUNDARENAUNIT:0:HP:6

---

# Undamaged_IsFour
#// The negative: with no damage he is his printed 4/6.

## GIVEN
CommonSetup: yyw/rrk
SkipPreGame: true
WithP1GroundArena: IC27_162:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:6

---

# Damaged_AttacksForEight
#// The bonus reaches combat: damaged Chewbacca hits P2's SOR_046 (3/7) for 8 and defeats it. At printed 4 it lives.

## GIVEN
CommonSetup: yyw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_162:1:1
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:4

---

# HealedToZero_BonusEnds
#// "While": healing the last damage ends it. IBH_066 (Vigilance, 1 + 2 off-aspect = 3) heals 2 from Chewbacca, who
#// had 1 — he is undamaged again and back to 4.

## GIVEN
CommonSetup: yyw/rrk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IBH_066
WithP1GroundArena: IC27_162:1:1

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:POWER:4

---

# ShieldAbsorbsTheHit_NotDamaged_NoBonus
#// A Shield prevents the damage, so he is never damaged and stays at 4. P2's SOR_046 attacks him (Sentinel — he is
#// the only legal target anyway).

## GIVEN
CommonSetup: yyw/rrk
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: IC27_162:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP2GroundArena: SOR_046:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:POWER:4

---

# Blanked_NoBonus
#// A unit that loses all abilities loses this one: SOR_138 Force Lightning (1 + 4 off-aspect = 5) on damaged
#// Chewbacca leaves him at 4 for the phase.

## GIVEN
CommonSetup: yyw/rrk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_138
WithP1GroundArena: IC27_162:1:1

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:POWER:4

---

# TakesDamage_ThenRequestBoundary_StillEight
#// He becomes damaged in play (defending against P2's SOR_046), then a request boundary: still 8 — nothing is
#// stored, the bonus is recomputed from his damage on every read.

## GIVEN
CommonSetup: yyw/rrk
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: IC27_162:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P2>AttackGroundArena:0:0
- P1>SimulateRequestBoundary

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:POWER:8
