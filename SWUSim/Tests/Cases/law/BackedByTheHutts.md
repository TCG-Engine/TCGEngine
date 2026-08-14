# DealsDamageEqualToCredits
#// LAW_247 Backed by the Hutts (Event, cost 3, Cunning) — Create a Credit token. You may deal damage
#//   to a unit equal to the number of friendly Credit tokens.
#//   P1 starts with 2 Credit tokens. Playing the event creates a 3rd FIRST, so the friendly count is 3
#//   when the damage resolves (proves create-then-count ordering, CR 3.13). 3 damage kills SEC_080 (3/3);
#//   a buggy count-before-create (2) would leave it alive.
#//   NOTE: with 2 usable Credit tokens in hand at play time, the credit-payment offer fires first — P1
#//   declines it (AnswerDecision:-), pays the 3 cost in resources, then the event resolves.
#// COVERAGE: offer=OfferIncludesFriendlyAndEnemyUnits (pending SELECTABLEEXACT across both sides) ·
#//           decline=DeclineDamage · reqboundary=BlankedCreditsStillCount (cross-player play before the
#//           event, count read after) · control=N/A (one-shot damage, no persistent per-unit marker) ·
#//           boundary pair=DealsDamageEqualToCredits (3 dmg exactly kills a 3-HP unit) +
#//           DamageAFriendlyUnit (1 dmg on a 3/3 survives)

## GIVEN
CommonSetup: yyw/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: LAW_247
WithP1Credits: 2
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1CREDITCOUNT:3
P2GROUNDARENACOUNT:0
P1RESAVAILABLE:0
P1NODECISION

---

# DeclineDamage
#// LAW_247 Backed by the Hutts — the damage is optional ("You may"). P1 declines it; the credit is still
#//   created. (Credit-payment offer declined first, then the damage MZMAYCHOOSE declined.)

## GIVEN
CommonSetup: yyw/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: LAW_247
WithP1Credits: 2
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
- P1>AnswerDecision:-

## EXPECT
P1CREDITCOUNT:3
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# OfferIncludesFriendlyAndEnemyUnits
#// LAW_247 Backed by the Hutts — "deal damage to A UNIT" is any unit, friendly or enemy. With no starting
#//   Credits (so no credit-payment offer), playing the event creates the first Credit, then the may-choose
#//   offer spans BOTH sides of the board. The decision is left PENDING so the offer itself is asserted.

## GIVEN
CommonSetup: yyw/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: LAW_247
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1CREDITCOUNT:1
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0
P1HASDECISION

---

# DamageAFriendlyUnit
#// LAW_247 Backed by the Hutts — the damage may be aimed at your OWN unit. Fresh single Credit (created
#//   by the event itself) → 1 damage onto the friendly SOR_095.

## GIVEN
CommonSetup: yyw/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: LAW_247
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1CREDITCOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# BlankedCreditsStillCount
#// LAW_247 Backed by the Hutts + SEC_046 Galen Erso — "the number of friendly Credit tokens" is a pure
#//   COUNT of tokens, so Credits blanked by Galen naming "Credit" still count. P2 plays Galen and names
#//   "Credit" (P1's 3 Credits lose all abilities — proven by NO pay-1-less offer appearing on P1's play).
#//   P1 plays the event: a 4th Credit is created and the damage is 4, not 0.

## GIVEN
CommonSetup: yyw/bbw/{myResources:3;theirResources:4}
SkipPreGame: true
WithActivePlayer: 2
WithP2Hand: SEC_046
WithP1Hand: LAW_247
WithP1Credits: 3
WithP1GroundArena: SOR_046:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Credit
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1CREDITCOUNT:4
P1GROUNDARENAUNIT:0:DAMAGE:4
P1RESAVAILABLE:0
P1NODECISION
