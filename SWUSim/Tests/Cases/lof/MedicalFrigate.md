# Heal2
#// LOF_250 Medical Frigate — On Attack: may heal 2 damage from another unit. It attacks the base and heals
#// 2 from the damaged friendly 3/7 (5 damage → 3).

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1SpaceArena: LOF_250:1:0
WithP1GroundArena: SOR_046:1:5

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# HealEnemyUnit
#// LOF_250 Medical Frigate — On Attack: may heal 2 damage from "another unit", which is unrestricted and
#// can be an enemy unit. It attacks the base and heals 2 from the opponent's damaged SOR_046 (5 → 3).

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1SpaceArena: LOF_250:1:0
WithP2GroundArena: SOR_046:1:5

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# HealDeclined
#// LOF_250 Medical Frigate — the heal is a "may": the controller can decline. It attacks the base, then
#// declines the heal; the damaged friendly SOR_046 stays at 5 damage and the Frigate is still exhausted
#// from the attack.

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1SpaceArena: LOF_250:1:0
WithP1GroundArena: SOR_046:1:5

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:5
P1SPACEARENAUNIT:0:EXHAUSTED

---

# CannotHealITSELF_AnotherUnitOnly
#// LOF_250 Medical Frigate — "heal 2 damage from ANOTHER unit" excludes the Frigate itself. It is damaged
#// too, but its own slot must not appear in the offer; only the two other friendly units do.
## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1SpaceArena: LOF_250:1:3
WithP1GroundArena: SOR_046:1:5
WithP1GroundArena: SOR_095:1:1
## WHEN
- P1>AttackSpaceArena:0:BASE
## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1
