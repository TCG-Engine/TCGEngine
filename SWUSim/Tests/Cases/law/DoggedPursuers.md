# PayDealTwoGround
#// LAW_198 Dogged Pursuers (Aggression, cost 5) — When Played: you may pay 1 resource. If you do, deal 2
#// damage to a ground unit. Pay 1, deal 2 to the enemy SOR_046.

## GIVEN
CommonSetup: rrw/bgw/{myResources:6}
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_198

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# DeclineNoDamage
#// LAW_198 Dogged Pursuers — When Played "you may pay 1 resource" is optional. Decline: no resource is
#// paid, no damage is dealt, the enemy SOR_046 is unharmed.

## GIVEN
CommonSetup: rrw/bgw/{myResources:6}
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_198

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# Offer_GroundPoolSpansBothSides_IncludesDeployedLeaders_ExcludesSpace
#// LAW_198 Dogged Pursuers — "deal 2 damage to A GROUND UNIT" names no controller, so the pool spans both
#// sides; it names an arena, so space is excluded; and a DEPLOYED LEADER is a unit in the ground arena, so
#// it belongs in the pool too. Board: friendly SOR_095 and the Pursuers themselves (self is not excluded),
#// the enemy SOR_046, and P2's deployed SOR_010 — all four in; the friendly SOR_237 and the enemy SOR_225
#// in the space arena are both out. The existing PayDealTwoGround section had a single enemy ground unit
#// and auto-targeted, so it could not have seen an enemy-only, friendly-only or leader-excluding pool.

## GIVEN
CommonSetup: rrw/bgw/{myResources:6;theirLeader:SOR_010:1:1:0}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_225:1:0
WithP1Hand: LAW_198

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:LAW_198
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&theirGroundArena-0&theirGroundArena-1

---

# Unaffordable_NoPayOfferAtAll
#// LAW_198 Dogged Pursuers — the optional pay is only offered when the resource exists. With exactly 5
#// resources the cost-5 play consumes all of them, so no prompt is raised, no damage is dealt and the
#// enemy SOR_046 is untouched. Boundary partner of PayDealTwoGround (6 resources → the offer appears).

## GIVEN
CommonSetup: rrw/bgw/{myResources:5}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_198

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:CARDID:LAW_198
P1RESAVAILABLE:0
