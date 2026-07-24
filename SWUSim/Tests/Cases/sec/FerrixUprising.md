# DamageTwicePerUnitInArena
#// SEC_130 Ferrix Uprising (event, cost 4) — Deal damage to a unit equal to twice the number of units you
#//   control in its arena. P1 controls 2 ground units → 4 damage to the enemy ground SOR_046.

## GIVEN
CommonSetup: ggk/rrk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SEC_041:1:0
WithP1GroundArena: SEC_042:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_130

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4

---

# SpaceArena_CountsSpaceUnits
#// SEC_130 Ferrix Uprising — the "2x units you control in the target's arena" counts the SPACE arena
#//   when the target is a space unit. P1 controls 2 space units → 4 damage to the enemy space LOF_119
#//   (4/10 → survives at DAMAGE:4). Ground units are irrelevant here.

## GIVEN
CommonSetup: ggk/rrk/{myResources:4}
P1OnlyActions: true
WithP1SpaceArena: SEC_213:1:0
WithP1SpaceArena: SOR_141:1:0
WithP2SpaceArena: LOF_119:1:0
WithP1Hand: SEC_130

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:4

---

# NoUnitsInTargetArena_ZeroDamage
#// SEC_130 Ferrix Uprising — if you control NO units in the target's arena, 2x0 = 0 damage. P1 controls
#//   only a ground unit; targeting the enemy SPACE unit deals 0 (LOF_119 stays at DAMAGE:0).

## GIVEN
CommonSetup: ggk/rrk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2SpaceArena: LOF_119:1:0
WithP1Hand: SEC_130

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:0

---

# OnlySameArenaUnitsCount
#// SEC_130 Ferrix Uprising — only units in the SAME arena as the target are counted. P1 has 3 ground +
#//   1 space; targeting an enemy GROUND unit deals 2x3 = 6 (the lone space unit is ignored). SOR_046
#//   (3/7) survives at DAMAGE:6.

## GIVEN
CommonSetup: ggk/rrk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SEC_041:1:0
WithP1GroundArena: SEC_042:1:0
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_141:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_130

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:6

---

# CanTargetOwnUnit
#// SEC_130 Ferrix Uprising — the target may be your OWN unit. P1 controls 2 ground units and targets
#//   one of them → 2x2 = 4 damage to the friendly SOR_046 (3/7 → survives at DAMAGE:4).

## GIVEN
CommonSetup: ggk/rrk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SEC_042:1:0
WithP1Hand: SEC_130

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:4

---

# DefeatsIfDamageExceedsHP
#// SEC_130 Ferrix Uprising — the dealt damage defeats a unit when it meets/exceeds its HP. P1 controls 3
#//   ground units → 6 damage to the enemy TWI_T02 (2/2) → defeated (ground arena empties).

## GIVEN
CommonSetup: ggk/rrk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SEC_041:1:0
WithP1GroundArena: SEC_042:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: TWI_T02:1:0
WithP1Hand: SEC_130

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
