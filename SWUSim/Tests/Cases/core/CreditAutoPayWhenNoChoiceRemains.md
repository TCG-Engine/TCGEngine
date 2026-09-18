# AutoPay_FromHand_PlaysWithoutAPrompt
#// ⚠ REGRESSION GUARD for the lone-CUSTOM bug (2026-09-18). When every usable Credit is required, the
#// Credit picker is skipped entirely (owner ruling: "auto-pick Credits when that is the only way to
#// pay"). The FIRST implementation did that by queueing the CREDIT_PAY resolver on its own — and a
#// CUSTOM queued with no interactive decision in front of it NEVER EXECUTES. The pair works only
#// because the CUSTOM is the answered MZMULTICHOOSE's continuation. The card was simply never played:
#// no unit, Credit untouched, no error anywhere. Fixed by invoking the resolver INLINE instead.
#// SOR_095 Battlefield Marine costs 2; 1 ready resource + 1 Credit is capacity exactly 2, so the Credit
#// is the only way to reach the cost and there is nothing left to decide.

## GIVEN
CommonSetup: ggw/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Credits: 1

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1CREDITCOUNT:0
P1RESAVAILABLE:0

---

# AutoPay_Unaffordable_StillRefusesAndSpendsNothing
#// ⚠ THE BOUNDARY PARTNER. Same card, but 0 ready resources and 1 Credit is capacity 1 against a cost
#// of 2 — genuinely unaffordable. SWUOfferAltPayment's guard at the top must still refuse it BEFORE any
#// token is touched (the game-3608 shape: an unaffordable play used to eat the Credit and still leave
#// the card in hand). Auto-pay must not become a way to spend Credits on a play that cannot complete.

## GIVEN
CommonSetup: ggw/rrk/{myResources:0}
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Credits: 1

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1CREDITCOUNT:1
