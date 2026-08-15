# HealThenDeal
#// LOF_246 Grogu — Hidden + Action [Exhaust]: heal up to 2 from a unit. If you do, deal that much to a
#// unit. P1 heals 2 from its damaged SOR_046 (2 → 0) and deals 2 to the enemy SEC_080.

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_246:1:0
WithP1GroundArena: SOR_046:1:2
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:Heal2
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# Heal1_Deal1
#// LOF_246 Grogu — the heal is "up to 2"; healing a unit that has only 1 damage heals 1, so exactly 1 damage
#// is then dealt. P1 heals its SOR_046 (1 damage → 0) and deals that 1 to the enemy SEC_080. Intended: "should heal
#// 1 damage from a unit and deal that much damage to a unit."
#// (Amount answer added 2026-08-14: "heal up to 2" now always offers the amount — Heal0/Heal1 here,
#// since the target has only 1 damage. Per the USER RULING the target pick is mandatory and Heal0 is
#// the soft pass.)

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_246:1:0
WithP1GroundArena: SOR_046:1:1
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:Heal1
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# HealZero_NoDamage
#// LOF_246 Grogu — the ability may heal nothing: targeting the undamaged Grogu heals 0, so the "if you do"
#// deal-damage step is skipped. Grogu still exhausts to pay the Action cost and no damage is dealt anywhere.
#// Intended: "should not heal damage from a unit and should not deal damage to a unit" (distribute 0).

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_246:1:0
WithP1GroundArena: SOR_046:1:2
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# SoftPass_Heal0_NoHealNoDeal_CostStillPaid
#// USER RULING (2026-08-14): for an "up to N" effect the TARGET pick is mandatory and the soft pass is
#// an amount of ZERO. P1 must name a unit (the damaged SOR_046), then takes Heal0: nothing is healed,
#// so the "if you do, deal that much damage to a unit" rider never fires and no deal-target is even
#// offered. Grogu still exhausts — the Action's cost was paid.

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_246:1:0
WithP1GroundArena: SOR_046:1:2
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:Heal0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION
