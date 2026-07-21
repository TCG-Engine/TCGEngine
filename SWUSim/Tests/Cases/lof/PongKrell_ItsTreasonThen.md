# CompletesAttack_DefeatWeak
#// LOF_038 Pong Krell (2/9) — Grit + "completes an attack (and survives): may defeat a unit with less
#// remaining HP than this unit's power." Krell (power 2) attacks the base (survives) and defeats the enemy
#// 3/1 (1 HP < 2).

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_038:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0

---

# MultipleTargets_SelectableSet
#// LOF_038 Pong Krell (power 2) — after completing an attack, the "defeat a unit with less remaining HP than
#// this unit's power" offer lists EXACTLY the units whose remaining HP < 2, i.e. only the two 3/1 Death Star
#// Stormtroopers (remaining HP 1); the 3/7 SOR_046 (remaining 7) is NOT offered — exactly the two weak units
#// are selectable, excluding the tougher unit.

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_038:1:0
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1

---

# MayDecline_NoDefeat
#// LOF_038 Pong Krell — the defeat is optional ("You may defeat..."). Krell attacks the base (survives), a
#// valid 3/1 target is present, but P1 declines (a pass-ability button is available): nothing is defeated and
#// the 3/1 stays in play.

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_038:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENACOUNT:1

---

# Grit_BoostsPowerIncludesSelf
#// LOF_038 Pong Krell (2/9, Grit) — with 6 damage on him his power is 2+6 = 8, so the defeat offer widens to
#// every unit with remaining HP < 8: the enemy 3/7 SOR_046 (remaining 7) AND Krell himself (remaining 9-6 =
#// 3). He survives attacking the base, and Krell himself is among the selectable targets.

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_038:1:6
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0
