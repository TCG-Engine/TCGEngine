# PayExpAnother
#// LAW_257 Hidden Hand Supplier (cost 1) — When Played: you may pay 1 resource. If you do, give an
#// Experience token to another unit. Pay 1; the only other unit (SOR_095) auto-targets.

## GIVEN
CommonSetup: bgw/bgw/{myResources:2}
WithP1GroundArena: SOR_095:1:0
WithP1Hand: LAW_257

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# DeclineNoExperience
#// LAW_257 Hidden Hand Supplier — When Played "you may pay 1 resource" is optional. Decline: no resource
#// is paid and no Experience token is given, so the other unit SOR_095 keeps 0 upgrades.

## GIVEN
CommonSetup: bgw/bgw/{myResources:2}
WithP1GroundArena: SOR_095:1:0
WithP1Hand: LAW_257

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# OfferPool_AnotherUnitSpansBothSidesAndBothArenas
#// LAW_257 Hidden Hand Supplier — offer assertion for "give an Experience token to ANOTHER unit". The only
#// printed restriction is "another", so the pool must be every unit in play except the Supplier itself: a
#// friendly ground unit, a friendly space unit, an ENEMY ground unit and an ENEMY space unit are all in;
#// the Supplier (myGroundArena-1, played this action) is out. The existing PayExpAnother section had a
#// single other unit and so auto-targeted — it could not have seen a friendly-only or ground-only pool.
#// The pay-1 gate is answered YES first so the target choose is the pending decision at end state.
#// COVERAGE: offer=OfferPool_AnotherUnitSpansBothSidesAndBothArenas (pending SELECTABLEEXACT: self excluded
#//           by "another", enemy units and both arenas included) · decline=DeclineNoExperience (the "you
#//           may pay 1 resource" NO branch) · boundary pair=PayExpAnother (paid -> 1 upgrade) vs
#//           DeclineNoExperience (declined -> 0 upgrades) · control=N/A (the Experience token attaches to
#//           the chosen unit and travels with it; no seat-bound marker is written) · reqboundary=not
#//           encoded (the pay answer and the target answer are separate requests in production; no
#//           serialize round-trip section exists yet)

## GIVEN
CommonSetup: bgw/bgw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_225:1:0
WithP1Hand: LAW_257

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:LAW_257
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0&theirGroundArena-0&theirSpaceArena-0

---

# LoneUnit_NoOtherTargetExists_NoPayOffer
#// LAW_257 Hidden Hand Supplier — an optional COST whose effect can only fizzle must not be offered; the
#// engine auto-declines instead of prompting (USER RULING 2026-08-13, recorded on LAW_227 Rookie
#// Rocket-jumper for this same "you may pay N, if you do …" family). With the Supplier as the ONLY unit in
#// play there is no "another unit" to receive the Experience token, so paying could only burn a resource
#// for nothing: no YES/NO prompt is raised and the second resource stays ready.
#// Boundary partner of PayExpAnother, which has one other unit on the board and does get the offer.

## GIVEN
CommonSetup: bgw/bgw/{myResources:2}
P1OnlyActions: true
WithP1Hand: LAW_257

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_257
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1RESAVAILABLE:1

---

# Unaffordable_NoPayOfferAtAll
#// LAW_257 Hidden Hand Supplier — the other half of the same gate: a legal target exists, but the resource
#// does not. With exactly 1 resource the cost-1 play consumes it, so no prompt is raised and SOR_095 gets
#// no Experience token. Together with LoneUnit_NoOtherTargetExists_NoPayOffer this pins both reasons the
#// offer can be withheld, and PayExpAnother is the case where neither applies.

## GIVEN
CommonSetup: bgw/bgw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: LAW_257

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1RESAVAILABLE:0
