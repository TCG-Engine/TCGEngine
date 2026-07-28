# DealOneToThreeGround
#// ASH_142 Mortar Trooper (Ground, 1/4) — Action [Exhaust]: deal 1 damage to each of up to 3 ground units.
#// P1 picks three enemy ground units (SEC_080, SOR_046, SOR_095); each takes 1.
## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: ASH_142:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1&theirGroundArena-2
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:1:DAMAGE:1
P2GROUNDARENAUNIT:2:DAMAGE:1

---

# DealToSingleUnit
#// ASH_142 Mortar Trooper — "up to 3" may be just one. P1 deals 1 to only SEC_080; the others are untouched.
## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: ASH_142:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:1:DAMAGE:0

---

# ChooseNoTargets
#// ASH_142 Mortar Trooper — "up to 3" allows choosing zero targets. P1 uses the ability but declines all
#// targets; the enemy unit is untouched, and Mortar Trooper still pays its exhaust cost.
## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: ASH_142:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:-
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# TargetSelf
#// ASH_142 Mortar Trooper — the ability targets "each of up to 3 ground units" including itself. P1 aims
#// the single target at Mortar Trooper; it takes 1 damage and is exhausted from the cost.
## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: ASH_142:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# MustBeReadyToUseAbility
#// ASH_142 Mortar Trooper — the ability costs [Exhaust], so an already-exhausted Mortar Trooper cannot use
#// it. The enemy unit takes no damage.
## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: ASH_142:0:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
