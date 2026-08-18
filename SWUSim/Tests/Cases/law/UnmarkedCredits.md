# CreatesCreditToken
#// LAW_244 Unmarked Credits (Event, cost 1, Cunning) — Create a Credit token.
#//   The token is created in the resource zone but is NOT a resource (RESCOUNT unchanged).

## GIVEN
CommonSetup: yyw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: LAW_244

## WHEN
- P1>PlayHand:0

## EXPECT
P1CREDITCOUNT:1
P1RESCOUNT:2
P1RESAVAILABLE:1
P1NODECISION

---

# P2Seat_TheCreditGoesToTheCASTER
#// LAW_244 Unmarked Credits — "Create a Credit token" creates it for the player resolving the event, so
#// the whole assertion is directional. P2 casts it from its own seat: P2 ends with 1 Credit and P1 with
#// none. The existing section is P1-only and would pass just as happily if the token were handed to a
#// hardcoded seat 1.

## GIVEN
CommonSetup: rrk/yyw/{}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 2
WithP2Hand: LAW_244

## WHEN
- P2>PlayHand:0

## EXPECT
P2CREDITCOUNT:1
P1CREDITCOUNT:0
P2RESAVAILABLE:1

---

# CreditsACCUMULATE_TwoCopiesMakeTwo
#// LAW_244 Unmarked Credits — a second copy adds to the pile rather than replacing it: two casts leave 2
#// Credits, and the resource row still reports 2 real resources because Credits are not resources. The
#// SPENDING behaviour of a Credit is deliberately not re-tested here — it is covered centrally in
#// Tests/Cases/core/CreditToken.md and CreditTokenPaysAbilityCosts.md; this card only has to create one.
#// ⚠ The FIRST cast leaves a Credit in hand-range of the SECOND: playing copy two therefore raises a
#// "spend Credits on this cost?" choose, which is declined so both copies are paid for with real
#// resources and both Credits survive to be counted.

## GIVEN
CommonSetup: yyw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: [LAW_244 LAW_244]

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1CREDITCOUNT:2
P1RESCOUNT:2
P1RESAVAILABLE:0
P1HANDCOUNT:0

---

# StacksOnTopOfCreditsAlreadyHeld
#// LAW_244 Unmarked Credits — creating is additive against a pre-existing pile, not a set-to-one. P1 starts
#// holding 2 Credits and finishes with 3.
#// ⚠ Holding Credits changes the FLOW: they can pay any cost, so playing the cost-1 event first raises a
#// "spend Credits on this cost?" choose. It is declined so the 2 starting Credits are still there to be
#// counted alongside the new one.

## GIVEN
CommonSetup: yyw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Credits: 2
WithP1Hand: LAW_244

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1CREDITCOUNT:3
P1RESAVAILABLE:1
