# PayN_ExpN
#// SEC_040 Emergency Powers (Event, cost 1) — choose a non-leader unit and pay any number of resources;
#//   give that many Experience tokens. Pay 2 → SOR_095 gets 2 Experience → 5/5.

## GIVEN
CommonSetup: bbk/grw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_040

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:2

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1NODECISION

---

# EnemyUnit_ExpEqualsResourcesPaid
#// SEC_040 Emergency Powers — the chosen non-leader unit may be an ENEMY unit. With no friendly units,
#//   the lone enemy SOR_232 auto-resolves as the target; paying 3 resources gives it 3 Experience tokens.
#//   Total resources exhausted = 1 (event) + 3 = 4 → 0 left ready.

## GIVEN
CommonSetup: bbk/grw/{myResources:4}
P1OnlyActions: true
WithP2GroundArena: SOR_232:1:0
WithP1Hand: SEC_040

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:3

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:3
P1RESAVAILABLE:0

---

# ChooseZeroResources_NoExperience
#// SEC_040 Emergency Powers — "pay ANY number of resources", so 0 is allowed. The lone friendly SOR_095
#//   auto-resolves as target; choosing 0 gives no Experience and exhausts no resources beyond the event's
#//   own cost 1 (started with 3 → 2 remain ready).

## GIVEN
CommonSetup: bbk/grw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_040

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1RESAVAILABLE:2

---

# NoUnitsInPlay_NoOp
#// SEC_040 Emergency Powers — with no non-leader units anywhere, the effect has no legal target and
#//   simply does nothing (no decision). The event still resolves (its cost 1 is paid → 2 of 3 left ready).

## GIVEN
CommonSetup: bbk/grw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SEC_040

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1RESAVAILABLE:2
