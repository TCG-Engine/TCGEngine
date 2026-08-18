# PayShieldSelf
#// LAW_227 Rookie Rocket-jumper (Cunning, cost 1) — When Played: you may pay 1 resource. If you do, give
#// a Shield token to this unit. Pay 1 -> self-shield.
#// COVERAGE: offer=N/A (no target picker; YES/NO pay only, asserted by PayShieldSelf/DeclinePayNoShield) ·
#//           decline=DeclinePayNoShield · control=N/A (self-only Shield, no cross-control marker) ·
#//           reqboundary=N/A (single YES/NO, no post-decision state re-read) · boundary pair=PayShieldSelf
#//           (pay drains the last ready resource) + DeclinePayNoShield (one resource left ready).
#//           NOTE: dies-on-entry (pay still offered while the Shield fizzles) is intentionally absent —
#//           see the open engine report for LAW_227; re-add once the When Played fires for a unit defeated
#//           on entry.

## GIVEN
CommonSetup: yyk/bgw/{myResources:2}
WithP1Hand: LAW_227

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_227
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1RESAVAILABLE:0

---

# DeclinePayNoShield
#// LAW_227 Rookie Rocket-jumper — the pay is optional. Decline -> no Shield, only the play cost (1) is
#//   spent, and the turn passes with no pending decision.

## GIVEN
CommonSetup: yyk/bgw/{myResources:2}
WithP1Hand: LAW_227

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_227
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1RESAVAILABLE:1
P1NODECISION

---

# DiesOnEntryUnderSnokeAura_NoPayOffer
#// USER RULING (2026-08-13, the Blue Leader pay-2 family): an optional COST whose effect can only
#// fizzle is NOT offered — the engine auto-declines instead of prompting. The 2/1 Rocket-jumper played
#// under enemy SHD_037 Snoke (-2/-2 to enemy non-leaders) enters at 0 HP and is defeated on entry; its
#// "you may pay 1 resource" would buy a Shield for a unit that no longer exists, so no prompt appears
#// and the resource stays ready. Intended divergence: prompting here would only let a player pay for
#// nothing.

## GIVEN
CommonSetup: yyk/bbk/{myResources:2}
P1OnlyActions: true
WithP1Hand: LAW_227
WithP2GroundArena: SHD_037:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1RESAVAILABLE:1

---

# Unaffordable_NoPayOfferAtAll
#// LAW_227 Rookie Rocket-jumper — the optional pay is gated on being able to pay it. With exactly 1
#// resource the cost-1 play consumes it, so no YES/NO prompt is raised at all, no Shield is created, and
#// the action ends cleanly. Boundary partner of PayShieldSelf (2 resources → the offer appears).

## GIVEN
CommonSetup: yyk/bgw/{myResources:1}
P1OnlyActions: true
WithP1Hand: LAW_227

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:CARDID:LAW_227
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1RESAVAILABLE:0
