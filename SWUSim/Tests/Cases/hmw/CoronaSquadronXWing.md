# Offer_OnlyYourExhaustedResources
#// COVERAGE: offer=this section · decline=Decline_ResourcesUnchanged_AttackStillResolves
#//           boundary=N/A (STRUCTURAL: no number or threshold — one resource)
#//           control=N/A (the X-Wing's own attack; "a resource" is read as the attacker's controller's)
#//           reqboundary=AcrossTheRequestBoundary · no-target=AllResourcesReady_NoPrompt
#//           path=AttackingAUnit_AlsoOffers (On Attack fires against a unit as well as a base)
#//           modes=2P only (no player reference; no friendly/enemy wording)
#//
#// HMW_209 Corona Squadron X-Wing — Unit (Space) 2/2, cost 2, [Cunning][Heroism], New Republic/Vehicle/Fighter.
#// "On Attack: You may ready a resource."
#// SHD_199 Coruscant Dissident's shape: the offer is your EXHAUSTED resources. P1 holds 3 ready + 2
#// exhausted; P2's exhausted resource must not be offered.

## GIVEN
CommonSetup: yyw/yyw
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: HMW_209:1:0
WithP1Resources: 3:SOR_046:1,2:SOR_046:0
WithP2Resources: 1:SOR_046:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1SELECTABLEEXACT:myResources-3&myResources-4

---

# ReadiesTheChosenResource

## GIVEN
CommonSetup: yyw/yyw
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: HMW_209:1:0
WithP1Resources: 3:SOR_046:1,2:SOR_046:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myResources-4

## EXPECT
P1RESAVAILABLE:4
P2BASEDMG:2
P1NODECISION

---

# Decline_ResourcesUnchanged_AttackStillResolves

## GIVEN
CommonSetup: yyw/yyw
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: HMW_209:1:0
WithP1Resources: 3:SOR_046:1,2:SOR_046:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P1RESAVAILABLE:3
P2BASEDMG:2
P1NODECISION

---

# AllResourcesReady_NoPrompt

## GIVEN
CommonSetup: yyw/yyw
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: HMW_209:1:0
WithP1Resources: 3:SOR_046:1

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1RESAVAILABLE:3
P2BASEDMG:2
P1NODECISION

---

# AttackingAUnit_AlsoOffers
#// Into P2's SOR_225 TIE/ln Fighter (2/1): the offer opens mid-combat; the X-Wing still trades.

## GIVEN
CommonSetup: yyw/yyw
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: HMW_209:1:0
WithP1Resources: 1:SOR_046:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:myResources-0

## EXPECT
P1RESAVAILABLE:1
P2SPACEARENACOUNT:0
P2BASEDMG:0

---

# AcrossTheRequestBoundary

## GIVEN
CommonSetup: yyw/yyw
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: HMW_209:1:0
WithP1Resources: 3:SOR_046:1,2:SOR_046:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myResources-3

## EXPECT
P1RESAVAILABLE:4
P2BASEDMG:2
