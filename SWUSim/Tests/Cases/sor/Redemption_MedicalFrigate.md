# HealNothing_NoSelfDamage
#// SOR_052 — "up to 8" permits healing nothing: the player assigns 0, so there is no self-damage and
#// the damaged unit stays damaged. Redemption enters at full HP.

## GIVEN
CommonSetup: bbw/bbw/{myResources:8;handCardIds:SOR_052}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:3

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
P1SPACEARENAUNIT:0:CARDID:SOR_052
P1SPACEARENAUNIT:0:DAMAGE:0

---

# HealUnitAndBase_SelfDamage
#// SOR_052 Redemption (Unit, Space, 6/9, Sentinel) — When Played: heal up to 8 total damage from any
#// number of units and/or bases, then deal that much (the ACTUAL healed) to itself. P1 heals 4 from a
#// damaged ground unit (4→0) + 2 from its base (3→1) = 6 total, so Redemption self-damages 6 (partial:
#// 6 of the 8 pool). Sentinel is auto-wired and not tested here.

## GIVEN
CommonSetup: bbw/bbw/{myResources:8;handCardIds:SOR_052;myBaseDamage:3}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:4    # 3/7 with 4 damage → healed to 0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0:4,myBase-0:2

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:1
P1SPACEARENAUNIT:0:CARDID:SOR_052
P1SPACEARENAUNIT:0:DAMAGE:6

---

# NoDamagedTargets_Fizzle
#// SOR_052 — no damaged units or bases anywhere: the heal has no targets, so no decision is queued
#// and Redemption simply enters at full HP. Absence guard for the empty-target path.

## GIVEN
CommonSetup: bbw/bbw/{myResources:8;handCardIds:SOR_052}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_052
P1SPACEARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# SelfDamageIsActualHealed
#// SOR_052 — the self-damage equals the ACTUAL healed, not the amount assigned. A unit with only 2
#// damage is over-assigned 6 heal; OnHealUnit clamps the heal to 2, so Redemption self-damages 2 (not
#// 6 and not the pool 8). Guards that "deal that much" reads actual-healed, not the assignment string.

## GIVEN
CommonSetup: bbw/bbw/{myResources:8;handCardIds:SOR_052}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:2    # 3/7 with 2 damage

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0:6

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1SPACEARENAUNIT:0:DAMAGE:2
