# DealThree
#// LAW_207 Attack From All Sides (Aggression event, cost 3) — "Deal 3 damage to a unit. If there are 4
#// or more different aspects among friendly units, you may deal 5 instead." With <4 aspects among
#// friendly units (P1 controls none), the deal is just 3.

## GIVEN
CommonSetup: rrk/bgw/{myResources:3}
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_207

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# FourAspectsDealFive
#// LAW_207 Attack From All Sides — with 4+ different aspects among friendly units (SOR_046 Vigilance/
#// Heroism + SEC_080 Command/Villainy = 4 distinct), opt to deal 5 instead. Target the enemy SOR_046.

## GIVEN
CommonSetup: rrk/bgw/{myResources:3}
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_207

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:5

---

# FourAspectsChooseThree
#// LAW_207 Attack From All Sides — with 4+ different aspects among friendly units you MAY deal 5, but you
#// can decline and deal the base 3. Target the enemy SOR_046, then choose 3.

## GIVEN
CommonSetup: rrk/bgw/{myResources:3}
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_207

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:NO

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# ExactlyThreeAspects_NoFiveDamageOfferAtAll
#// LAW_207 — BOUNDARY PAIR with FourAspectsDealFive. "If there are 4 or more different aspects among
#// friendly units, you may deal 5 instead." This is the ONE-SHORT half: P1 controls SOR_046
#// (Vigilance/Heroism) + SOR_095 (Command/Heroism) = exactly THREE distinct aspects, one under the gate,
#// so the "deal 5?" offer must never be raised and the damage stays at the base 3.
#// The existing DealThree section is NOT this boundary — P1 controls no friendly units there at all
#// (zero aspects), so it cannot distinguish "3 is below the gate" from "the gate is checked at all".
#// FourAspectsDealFive uses the same SOR_046 plus SEC_080 (Command/Villainy) for a 4th aspect, so the
#// two boards differ by exactly one aspect.
#// P1NODECISION is the load-bearing assertion: it proves the offer was never made, not merely declined.

## GIVEN
CommonSetup: rrk/bgw/{myResources:3}
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_207

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION
