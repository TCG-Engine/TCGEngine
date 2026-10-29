# OnAttack_ShieldsAnotherFriendlyResistanceUnit
#// IC27_040 Rose — In the Fight (6/6 Ground, Vigilance/Heroism, Resistance, unique)
#// "Shielded. On Attack: You may give a Shield token to another Resistance unit."
#// Shielded is printed (auto-wired). The On Attack is SOR_050 The Ghost's clause with a different trait:
#// SWUOfferUnitTarget(traits Resistance, excludeSelf, may) -> GIVE_SHIELD. "another Resistance unit" has no
#// "friendly", so ENEMY Resistance units are legal too.
#//
#// COVERAGE: offer=Offer_OtherResistanceUnits_BothSides_RoseExcluded ·
#//           decline=Decline_NoShield ·
#//           boundary=N/A (no numeric threshold) ·
#//           control=N/A (no owner-scoped zone, no "your") ·
#//           reqboundary=OnAttack_AnsweredAcrossRequestBoundary ·
#//           modes=2P only (no player reference, no friendly/enemy wording — the pool is the whole table)
#//
#// Rose (seeded — no Shield of her own) attacks P2's base; P1 gives the Shield to Kaydel Connix.

## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_040:1:0   # idx 0 — Rose 6/6
WithP1GroundArena: IC27_032:1:0   # idx 1 — Kaydel Connix 1/5 (Resistance)
WithP2GroundArena: IC27_031:1:0   # enemy BB-8 0/4 (Resistance)

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2BASEDMG:6
P1NODECISION

---

# Offer_OtherResistanceUnits_BothSides_RoseExcluded
#// The pool: P1's Kaydel Connix and P2's BB-8 (both Resistance). Rose herself ("another") and P1's Battlefield
#// Marine (Rebel, not Resistance) are excluded. Left pending.

## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_040:1:0
WithP1GroundArena: IC27_032:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: IC27_031:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-1&theirGroundArena-0

---

# EnemyResistanceUnit_CanTakeIt
#// "another Resistance unit" includes an ENEMY one.

## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_040:1:0
WithP1GroundArena: IC27_032:1:0
WithP2GroundArena: IC27_031:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:1:SHIELDCOUNT:0

---

# Decline_NoShield
#// "You may" — declining gives nothing to anyone; the attack still lands.

## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_040:1:0
WithP1GroundArena: IC27_032:1:0
WithP2GroundArena: IC27_031:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:1:SHIELDCOUNT:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2BASEDMG:6
P1NODECISION

---

# SingleCandidate_StillDeclinable
#// One legal target is still an optional offer (MZMAYCHOOSE does not auto-resolve) — declined here.

## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_040:1:0
WithP1GroundArena: IC27_032:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:1:SHIELDCOUNT:0
P2BASEDMG:6

---

# NoOtherResistanceUnit_NoPrompt
#// No other Resistance unit anywhere: nothing is offered and the attack resolves normally.

## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_040:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1NODECISION
P2BASEDMG:6
P1GROUNDARENAUNIT:1:SHIELDCOUNT:0

---

# OnAttack_AnsweredAcrossRequestBoundary

## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_040:1:0
WithP1GroundArena: IC27_032:1:0
WithP2GroundArena: IC27_031:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1
P2BASEDMG:6
