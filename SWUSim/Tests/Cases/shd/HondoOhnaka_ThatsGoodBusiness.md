# Front_Decline
#// SHD_005 Hondo Ohnaka (front) — declining the "may" leaves Hondo ready and grants no Experience token.
#// COVERAGE: offer=N/A (the Experience target pick is answered directly; the pool is every unit in play
#//           and is not narrowed by the ability) · decline=Front_Decline · control=N/A (the trigger is
#//           gated on "when YOU play a card using Smuggle" — it is seat-scoped by construction and no
#//           object changes controller) · boundary=Front_SmugglePlayGivesExp (accept: leader exhausted,
#//           token given) vs Front_Decline (decline: leader ready, no token) · reqboundary=
#//           Front_SmugglePlayGivesExp (the leader's exhaust state is read after the YES is answered)
#//           NOTE (superseded 2026-08-16): the DEPLOYED side ("…You may give an Experience token to a
#//           unit", no exhaust cost) used to be unimplemented and is now wired and covered by the three
#//           Deployed_* sections below.

## GIVEN
CommonSetup: ggk/ggk/{myLeader:SHD_005}
P1OnlyActions: true
WithP1Resources: 1:SHD_065:0,10:SOR_095:1
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>SmuggleResource:0
- P1>AnswerDecision:-

## EXPECT
P1LEADER:READY
P1GROUNDARENAUNIT:0:POWER:3

---

# Front_SmugglePlayGivesExp
#// SHD_005 Hondo Ohnaka (front, undeployed) — "When you play a card using Smuggle: You may exhaust this
#// leader. If you do, give an Experience token to a unit." P1 plays SHD_065 from resources via Smuggle,
#// accepts (exhausting Hondo), and gives an Experience token (+1/+1) to its SOR_046 (3/7 → 4 power).

## GIVEN
CommonSetup: ggk/ggk/{myLeader:SHD_005}
P1OnlyActions: true
WithP1Resources: 1:SHD_065:0,10:SOR_095:1
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>SmuggleResource:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1LEADER:EXHAUSTED
P1GROUNDARENAUNIT:0:POWER:4

---

# Deployed_SmugglePlayGivesExp_NoExhaustCost
#// SHD_005 Hondo Ohnaka (DEPLOYED) — "When you play a card using Smuggle: You may give an Experience
#// token to a unit." The deployed side has NO exhaust cost (that is the front side's price), so the
#// offer is a plain "may give", not a YES/NO-to-pay.
#// Same fixture as Front_SmugglePlayGivesExp except Hondo is deployed: P1 smuggles SHD_065 out of its
#// resources and puts the Experience on SOR_046 (3/7 -> 4 power).
#// ⚠ RED: the deployed arm is unimplemented. Both the shared dispatch (_SWUSmuggleFireEntry) and the
#// card's own Shd005FrontReaction gate on _SWULeaderReadyUndeployed, so a DEPLOYED Hondo never fires at
#// all — the "deployed side unimplemented" family (SEC_009 / SEC_012 / LOF_017).

## GIVEN
CommonSetup: ggk/ggk/{myLeader:SHD_005:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1:SHD_065:0,10:SOR_095:1
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>SmuggleResource:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4

---

# Deployed_SmuggleRaisesAPendingOffer
#// SHD_005 Hondo (deployed) — the offer must actually be RAISED. Left UNANSWERED so the pending
#// decision is the assertion, and the pool is every unit in play (the ability says "a unit", no
#// controller or arena word) — P1's SOR_046, deployed Hondo himself at ground index 1, AND the unit
#// just smuggled into the space arena, which is already in play when the reaction resolves.
#// ⚠ This section is the load-bearing one for the decline pair below: with no offer raised at all,
#// "declining" is indistinguishable from "nothing happened", so the decline section alone proves
#// nothing. Verified: it FAILS with the deployed arm reverted.

## GIVEN
CommonSetup: ggk/ggk/{myLeader:SHD_005:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1:SHD_065:0,10:SOR_095:1
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>SmuggleResource:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&mySpaceArena-0

---

# Deployed_SmuggleOfferIsDeclinable
#// SHD_005 Hondo (deployed) — the deployed reaction is a "you may", so declining grants nothing and
#// leaves no dangling decision. Only meaningful together with the pending-offer section above.

## GIVEN
CommonSetup: ggk/ggk/{myLeader:SHD_005:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1:SHD_065:0,10:SOR_095:1
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>SmuggleResource:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1NODECISION
