# GivesShield
#// SOR_073 Moment of Peace (Event, cost 1) — "Give a Shield token to a unit."
#// P1's only unit (Battlefield Marine) is the sole target → auto-receives a shield.
#// COVERAGE: offer=Offer_IncludesEnemyAndShieldedUnits (pending SELECTABLEEXACT: friendly and
#//           ENEMY units, already-shielded included) · decline=N/A (the shield give is
#//           mandatory) · control=N/A (one-shot token give; enemy-unit targeting asserted in
#//           AlreadyShielded_GainsSecondShield) · boundary=0 shields → 1 (GivesShield) vs
#//           1 shield → 2 stacking (AlreadyShielded_GainsSecondShield) ·
#//           reqboundary=AlreadyShielded_GainsSecondShield (play and pick span separate requests)

## GIVEN
CommonSetup: bbk/bbk/{myResources:1;handCardIds:SOR_073}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# Offer_IncludesEnemyAndShieldedUnits
#// SOR_073 Moment of Peace — the target pool is ANY unit: P1's ground unit and P2's
#// already-shielded space unit are both offered (two candidates → the pick stays pending,
#// asserted here without answering).

## GIVEN
CommonSetup: bbk/bbk/{myResources:1;handCardIds:SOR_073}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_060:1:0
WithP2SpaceArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirSpaceArena-0

---

# AlreadyShielded_GainsSecondShield
#// SOR_073 Moment of Peace — shielding a unit that ALREADY has a Shield token stacks a
#// second one: P2's shielded Distant Patroller is chosen and ends with 2 Shields.

## GIVEN
CommonSetup: bbk/bbk/{myResources:1;handCardIds:SOR_073}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_060:1:0
WithP2SpaceArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENAUNIT:0:SHIELDCOUNT:2
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
