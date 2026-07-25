# GrantOnAttackCredit
#// LAW_169 Payroll Heist (Command event, cost 4) — "For this phase, each friendly unit gains: On Attack:
#// Create a Credit token." After playing it, SOR_095 attacks the base and creates a Credit token.

## GIVEN
CommonSetup: ggw/bgw/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: LAW_169

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:BASE

## EXPECT
P1CREDITCOUNT:1
P2BASEDMG:3

---

# GrantsAllFriendlyUnits_GroundAndSpace
#// LAW_169 Payroll Heist — the buff hits EVERY friendly unit (both arenas). A ground unit (SOR_095) and a
#// space unit (SOR_237) each attack the base and each creates a Credit token → 2 Credits. Base takes
#// 3 (ground) + 2 (space) = 5.

## GIVEN
CommonSetup: ggw/bgw/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP1Hand: LAW_169

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:BASE
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1CREDITCOUNT:2
P2BASEDMG:5

---

# NoUnitsNoEffectStillPlays
#// LAW_169 Payroll Heist — with no friendly units in play there is nothing to grant the ability to; the
#// event still resolves with no effect and goes to the discard pile. No Credit tokens are created.

## GIVEN
CommonSetup: ggw/bgw/{myResources:4}
P1OnlyActions: true
WithP1Hand: LAW_169

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1CREDITCOUNT:0
