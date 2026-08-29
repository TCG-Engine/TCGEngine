# Credit_IsNOTDestroyedByAPaymentThatCannotSucceed
#// Live report (game 3608, 2R + 1C): "if I click Confirm it says I need 5R and my Credit disappears
#// into oblivion."
#//
#// ROOT CAUSE: SWUOfferAltPayment raised the Credit picker whenever the player held ANY usable Credit,
#// without first asking whether the cost was reachable AT ALL. CREDIT_PAY then defeats the chosen
#// tokens and only afterwards dispatches the play — whose own affordability check fails and aborts.
#// The tokens are already gone. Defeating a Credit is permanent and unconditional, so offering a
#// payment that cannot complete can only destroy resources for nothing (the "fizzle-only optional must
#// not be offered" rule, with real property damage attached).
#//
#// ⚠ This is NOT piloting-specific — measured on a plain unit before any fix. Any card you cannot
#// afford eats a ticked Credit.
#//
#// SOR_046 Consular Security Force is cost 4 and Vigilance+Heroism; under an Aggression base with a
#// Command/Heroism leader the Vigilance pip is uncovered (+2), so it costs 6. Total payment capacity is
#// 3 (2 ready resources + 1 Credit) — not close — so no offer should appear and nothing should be spent.

## GIVEN
CommonSetup: rgw/bbw
WithP1Resources: 2:SOR_095:1
WithP1Credits: 1
WithP1Hand: [SOR_046]
WithActivePlayer: 1
WithInitiativePlayer: 1

## WHEN
- P1>PlayHand:0

## EXPECT
P1CREDITCOUNT:1
P1HANDCOUNT:1
P1RESAVAILABLE:2
P1NODECISION

---

# Credit_IsSTILLOffered_WhenTheCostIsActuallyReachable
#// THE CONTROL, and the reason the guard is "capacity < cost" rather than "never offer".
#// Same board, but SEC_080 Imperial Dark Trooper costs 2 base and its Command pip is covered by the
#// leader while its Villainy pip is not (+2) — so 4... no: with 2 ready + 1 Credit the capacity is 3.
#// SOR_095 Battlefield Marine is Command+Heroism, both pips covered by the Leia-style leader, cost 2 —
#// affordable outright, so the Credit offer must still be raised (paying 1 with a Credit and 1 with a
#// resource is a legal, sometimes desirable, choice).
#// Without this section the fix could be "stop offering Credits" and still look correct above.

## GIVEN
CommonSetup: rgw/bbw
WithP1Resources: 2:SOR_095:1
WithP1Credits: 1
WithP1Hand: [SOR_095]
WithActivePlayer: 1
WithInitiativePlayer: 1

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Defeat_any_number_of_Credit_tokens_to_pay_1_resource_less_each

---

# Credit_PaidNormally_WhenTheCreditIsWhatMakesItAffordable
#// The whole point of Credits, and the case the guard must not break: capacity 3 against a cost of 3.
#// SOR_046 is unaffordable above at cost 6; here the same 2R + 1C pays a cost-3 card outright by
#// defeating the Credit and exhausting 2 resources.
#// SOR_213 Syndicate Lackeys is cost 3 and Cunning — uncovered here (+2) — so instead use a card whose
#// pips the leader covers: SOR_046 at cost 4 is still too dear, so this uses ASH_261 Noti Mobile Pod,
#// a NEUTRAL 3-cost vanilla with no aspect pips at all and therefore exactly 3.

## GIVEN
CommonSetup: rgw/bbw
WithP1Resources: 2:SOR_095:1
WithP1Credits: 1
WithP1Hand: [ASH_261]
WithActivePlayer: 1
WithInitiativePlayer: 1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1CREDITCOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:ASH_261
P1HANDCOUNT:0
P1RESAVAILABLE:0
