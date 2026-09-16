# SharedTrait_OffersAnyUnitOrBase
#// COVERAGE: offer=this section · decline=N/A (STRUCTURAL: mandatory once the condition holds)
#//           boundary=N/A (STRUCTURAL: a trait match, not a number) · negative=NoSharedTrait_NoHeal
#//           leaders=OnlyALeaderUnit_IsNotANonLeaderUnit and DeployedLeaderIsStillAFriendlyLeader
#//           control=N/A · reqboundary=AcrossTheRequestBoundary
#//           modes=2P,TeamSuns (text says "friendly") — TeamSuns_TeammatesUnitSatisfiesIt
#//
#// HMW_098 Resonate — Event, cost 1, [Vigilance], Innate.
#// "If a friendly non-leader unit shares a Trait with a friendly leader, heal 4 damage from a unit or base."
#// Leader SOR_002 Iden Versio is Imperial/Trooper; SEC_080 Imperial Dark Trooper shares both.

## GIVEN
CommonSetup: bbk/bbk/{myResources:1;myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_098
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:5

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0&myBase-0&theirBase-0

---

# HealsYourBase

## GIVEN
CommonSetup: bbk/bbk/{myResources:1;myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_098
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myBase-0

## EXPECT
P1BASEDMG:1

---

# CanHealAnEnemyUnit

## GIVEN
CommonSetup: bbk/bbk/{myResources:1;myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_098
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:5

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P1BASEDMG:5

---

# NoSharedTrait_NoHeal
#// SOR_237 Alliance X-Wing is Rebel/Vehicle/Fighter — no trait in common with Iden's Imperial/Trooper

## GIVEN
CommonSetup: bbk/bbk/{myResources:1;myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_098
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:5
P1NODECISION

---

# OnlyALeaderUnit_IsNotANonLeaderUnit
#// Iden deployed is a friendly leader AND a unit — but not a NON-leader unit. Nothing else in play.

## GIVEN
CommonSetup: bbk/bbk/{myResources:1;myBaseDamage:5;myLeader:SOR_002:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_098

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:5
P1NODECISION

---

# DeployedLeaderIsStillAFriendlyLeader

## GIVEN
CommonSetup: bbk/bbk/{myResources:1;myBaseDamage:5;myLeader:SOR_002:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_098
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myBase-0

## EXPECT
P1BASEDMG:1

---

# TeamSuns_TeammatesUnitSatisfiesIt

## GIVEN
CommonSetup: bbk/yyw/{myResources:1;myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: HMW_098
WithP3GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myBase-0

## EXPECT
P1BASEDMG:1

---

# AcrossTheRequestBoundary

## GIVEN
CommonSetup: bbk/bbk/{myResources:1;myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_098
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:5

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myBase-0

## EXPECT
P1BASEDMG:1
