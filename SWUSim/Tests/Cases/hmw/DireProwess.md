# Offer_AnyUnit_HostIncluded
#// COVERAGE: offer=this section · decline=Decline_NoToken
#//           boundary=N/A (STRUCTURAL: no number or threshold — "a unit" with no qualifier)
#//           control=N/A (no owner-scoped zone or "your" wording; the token lands on the chosen unit)
#//           reqboundary=AcrossTheRequestBoundary
#//           no-target=N/A (STRUCTURAL: the upgrade's own host is "a unit" while its When Played resolves)
#//           dispatch=AttachedToAnEnemyUnit_StillOffers (the When Played rides the host, whoever controls it)
#//           modes=2P only (no player reference; no friendly/enemy wording)
#//
#// HMW_097 Dire Prowess — Upgrade +1/+1, cost 2, [Vigilance], Learned.
#// "When Played: You may give a Weakness token to a unit."
#// Attach is to "a unit" (CR 2.e), so with enemy units present the host is a real choice — every section
#// picks P1's SOR_095 first, then the When Played offer opens.

## GIVEN
CommonSetup: bbk/bbk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_097
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: [SOR_046:1:0 SOR_128:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1DECISIONTOOLTIP:Choose_a_unit
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0&theirGroundArena-1

---

# GivesWeaknessToAnEnemyUnit
#// SOR_046 3/7 → 2/6 with the token.

## GIVEN
CommonSetup: bbk/bbk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_097
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: [SOR_046:1:0 SOR_128:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:0:HP:6
P1GROUNDARENAUNIT:0:POWER:4

---

# WeaknessDefeatsAOneHpUnit
#// SOR_128 Death Star Stormtrooper 3/1 → 0 HP, defeated on the spot (no later action needed).

## GIVEN
CommonSetup: bbk/bbk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_097
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: [SOR_046:1:0 SOR_128:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2DISCARDCOUNT:1

---

# CanTargetItsOwnHost
#// SOR_095 3/3 +1/+1 (Dire Prowess) -1/-1 (Weakness) = 3/3 with two subcards.

## GIVEN
CommonSetup: bbk/bbk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_097
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# Decline_NoToken

## GIVEN
CommonSetup: bbk/bbk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_097
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION

---

# AttachedToAnEnemyUnit_StillOffers
#// No friendly unit: the upgrade auto-attaches to P2's lone SOR_046 (attach to "a unit", CR 2.e), and the
#// When Played still offers — its host is the only unit, and the offer is a MAY so it still prompts.

## GIVEN
CommonSetup: bbk/bbk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_097
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:2
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:HP:7

---

# AcrossTheRequestBoundary

## GIVEN
CommonSetup: bbk/bbk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_097
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: [SOR_046:1:0 SOR_128:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:POWER:2
