# DeclineCredits_AutoToppedUp_PlayGoesThrough
#// ⚠ BUG REPORT #1048, game 522237. HMW_048 Vernestra Rwoh, cost 6: "As an additional cost to play this
#// unit, put up to 2 units that each cost 5 or less from your discard pile on the bottom of your deck."
#//
#// THE REPORTED BOARD: 4 ready resources + 2 Credits = capacity exactly 6. The additional-cost gate
#// (_SWUPlayIsPayableAtDiscount) CORRECTLY said payable — capacity is all three tiers, CR 3.13 — so the
#// two discard units were bottomed. The Credit prompt is only raised AFTER that, inside
#// SWUContinuePlayAfterExploit, and the player confirmed ZERO Credits ("forgot to select credits"), so
#// SWUPayCost had 4 resources against a cost of 6 and the play failed. CREDIT_PAY's CR 4.a gate
#// protected the CREDITS ("nothing was spent") but could not protect the additional cost one level up:
#// the units were gone from the discard and unselectable on the retry.
#//
#// THE FIX (owner, 2026-09-18): "auto-pick Credits when that is the only way to pay." Credits that ready
#// resources and SEC_122 Droids together cannot cover are no longer declinable — the picker's lower
#// bound becomes the required count, and CREDIT_PAY tops a short answer up server-side (the
#// MZMULTICHOOSE bound is CLIENT-enforced only, so the server needs its own copy).
#// So the half-applied play is unreachable: the payment always completes, and the additional cost that
#// was already applied is legitimately due.
#// SHD_080 Salacious Crumb's gained "When Played: heal 1 from your base" firing (5 -> 4) is what proves
#// she actually entered play rather than the cost being skipped.

## GIVEN
CommonSetup: gyw/rrk/{myResources:4;myBaseDamage:5}
P1OnlyActions: true
WithP1Hand: HMW_048
WithP1Discard: [SHD_080 SOR_046]
WithP1Deck: [SOR_095 SOR_128]
WithP1Credits: 2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0&myDiscard-1
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_048
P1DISCARDCOUNT:0
P1DECKCOUNT:4
P1BASEDMG:4
P1CREDITCOUNT:0
P1RESAVAILABLE:0

---

# SpendAboveTheMinimum_ExtraCreditsSaveResources
#// ⚠ THE OTHER HALF OF THE CHOICE. 4 ready resources + THREE Credits against a cost of 6: two Credits
#// are required, so the third is the player's to spend or keep. Here they spend ALL THREE, which is a
#// real and reasonable line — Credits are worth less than resources to a deck that wants to keep
#// resources ready — and the engine must honour it rather than capping the payment at the minimum.
#// 3 Credits + 3 resources = 6, leaving 1 resource ready. Pairs with RealChoiceRemains_PromptIsStillRaised
#// below, which spends only the required two and keeps the third.
#//
#// ⚠ This section previously used the 4-resource/2-Credit fixture and picked both Credits explicitly.
#// Part 2 (auto-pay) removed the picker from that fixture entirely, which made the section VACUOUS — it
#// passed with a deliberately nonsensical answer, because the harness silently ignores an action with no
#// pending decision to answer. Rewritten with a fixture where the picker genuinely still appears.

## GIVEN
CommonSetup: gyw/rrk/{myResources:4;myBaseDamage:5}
P1OnlyActions: true
WithP1Hand: HMW_048
WithP1Discard: [SHD_080 SOR_046]
WithP1Deck: [SOR_095 SOR_128]
WithP1Credits: 3

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0&myDiscard-1
- P1>AnswerDecision:myTempZone-0&myTempZone-1&myTempZone-2

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_048
P1DISCARDCOUNT:0
P1DECKCOUNT:4
P1BASEDMG:4
P1CREDITCOUNT:0
P1RESAVAILABLE:1

---

# CreditsNotRequired_DeclineIsStillHonoured
#// ⚠ THE CONTROL, and the one that stops this fix from becoming a worse bug. 6 ready resources against a
#// cost of 6, with 2 Credits in hand: the Credits are NOT the only way to pay, so declining them must
#// still be honoured and both must survive. Without this section the fix above passes for an
#// implementation that force-spends Credits on every play that has any.
#// The play still goes through on resources alone: 6 - 6 = 0 resources left, 2 Credits kept.

## GIVEN
CommonSetup: gyw/rrk/{myResources:6;myBaseDamage:5}
P1OnlyActions: true
WithP1Hand: HMW_048
WithP1Discard: [SHD_080 SOR_046]
WithP1Deck: [SOR_095 SOR_128]
WithP1Credits: 2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0&myDiscard-1
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_048
P1DISCARDCOUNT:0
P1BASEDMG:4
P1CREDITCOUNT:2
P1RESAVAILABLE:0

---

# PartialPick_ToppedUpToTheMinimumOnly
#// ⚠ The server-side half of the bound. 3 ready resources + 3 Credits against a cost of 6: exactly 3
#// Credits are required. The player picks only ONE, which the client would have refused — so this is the
#// crafted/short answer CREDIT_PAY must handle itself. It tops up to 3, never to the cap: the play goes
#// through and NO Credit beyond the required three is touched (0 left here, since 3 of 3 were needed).
#// Pairs with CreditsNotRequired_DeclineIsStillHonoured — together they pin the minimum from both sides.

## GIVEN
CommonSetup: gyw/rrk/{myResources:3;myBaseDamage:5}
P1OnlyActions: true
WithP1Hand: HMW_048
WithP1Discard: [SHD_080 SOR_046]
WithP1Deck: [SOR_095 SOR_128]
WithP1Credits: 3

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0&myDiscard-1
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_048
P1DISCARDCOUNT:0
P1BASEDMG:4
P1CREDITCOUNT:0
P1RESAVAILABLE:0

---

# NoChoiceLeft_CreditsAutoPaid_NoPromptAtAll
#// ⚠ OWNER RULING 2026-09-18, part 2: "auto-pick Credits when that is the only way to pay." Part 1 made
#// the required Credits non-declinable; this removes the prompt entirely when there is NOTHING LEFT TO
#// DECIDE. 4 ready resources + 2 Credits against a cost of 6: every Credit is required, so the only
#// legal answer is "both" and the modal is a click that cannot go any other way.
#// The reported flow (bug #1048) was exactly this shape, which is why a misclick was even possible.
#// The picker is skipped but CREDIT_PAY still runs, so the Credits are genuinely defeated, the game-log
#// line is still written, and LAW_015 Jabba's Credit-paid Ambush grant still arms — none of that is
#// duplicated into a second code path.
#// P1NODECISION is the assertion that carries the feature: after answering only the DISCARD picker the
#// play is already complete, with no Credit modal waiting.

## GIVEN
CommonSetup: gyw/rrk/{myResources:4;myBaseDamage:5}
P1OnlyActions: true
WithP1Hand: HMW_048
WithP1Discard: [SHD_080 SOR_046]
WithP1Deck: [SOR_095 SOR_128]
WithP1Credits: 2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0&myDiscard-1

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_048
P1DISCARDCOUNT:0
P1BASEDMG:4
P1CREDITCOUNT:0
P1RESAVAILABLE:0

---

# RealChoiceRemains_PromptIsStillRaised
#// ⚠ THE BOUNDARY PARTNER, and the one that stops part 2 becoming "never ask about Credits again".
#// 4 ready resources + THREE Credits against a cost of 6: two Credits are required but a third is
#// optional, so there IS a decision — spend 2 and keep 1, or spend 3 and keep a resource. The prompt
#// must still be raised, and the player must still be able to answer it.
#// Here they spend the minimum two, keeping the third Credit and spending all 4 resources.

## GIVEN
CommonSetup: gyw/rrk/{myResources:4;myBaseDamage:5}
P1OnlyActions: true
WithP1Hand: HMW_048
WithP1Discard: [SHD_080 SOR_046]
WithP1Deck: [SOR_095 SOR_128]
WithP1Credits: 3

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0&myDiscard-1
- P1>AnswerDecision:myTempZone-0&myTempZone-1

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_048
P1DISCARDCOUNT:0
P1BASEDMG:4
P1CREDITCOUNT:1
P1RESAVAILABLE:0
