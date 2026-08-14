# DefeatCreditDualExp
#// LAW_040 Taramyn Barcona (4/6) — When Played: you may defeat a Credit token (any player's). If you do,
#// give an Experience token to this unit and another friendly unit. Defeat P2's lone Credit token; Exp to
#// Taramyn and to SEC_080.

## GIVEN
CommonSetup: bgw/bgw/{myResources:5;theirResources:0}
WithP2Credits: 1
WithP1GroundArena: SEC_080:1:0
WithP1Hand: LAW_040

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirResources-0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:LAW_040
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2CREDITCOUNT:0

---

# DefeatFriendlyCredit_DualExp
#// LAW_040 Taramyn Barcona — the "defeat a Credit (any player's)" may target a FRIENDLY Credit too. P1
#// holds 1 Credit; after playing Taramyn (paying the cost without spending the Credit) P1 defeats its own
#// Credit, then Taramyn and the lone other friendly unit SEC_080 each gain an Experience token.

## GIVEN
CommonSetup: bgw/bgw/{myResources:6;theirResources:0}
WithP1Credits: 1
WithP1GroundArena: SEC_080:1:0
WithP1Hand: LAW_040

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:DONE
- P1>AnswerDecision:myResources-6

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:LAW_040
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1CREDITCOUNT:0

---

# MayDeclineCreditDefeat_NoExp
#// LAW_040 Taramyn Barcona — the ability is optional ("you may defeat a Credit"). With a Credit available,
#// P1 declines. Because no Credit is defeated, the "if you do" clause never fires: neither Taramyn nor
#// SEC_080 gains an Experience token, and the Credit remains.

## GIVEN
CommonSetup: bgw/bgw/{myResources:6;theirResources:0}
WithP1Credits: 1
WithP1GroundArena: SEC_080:1:0
WithP1Hand: LAW_040

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:DONE
- P1>AnswerDecision:PASS

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:LAW_040
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1CREDITCOUNT:1

---

# NobodyHasCredits_NoExp
#// LAW_040 Taramyn Barcona — if no player has a Credit token there is nothing to defeat, so the "if you do"
#// clause is skipped and no Experience tokens are handed out. Taramyn plays as a plain 4/6 and SEC_080 is
#// untouched.

## GIVEN
CommonSetup: bgw/bgw/{myResources:5;theirResources:0}
WithP1GroundArena: SEC_080:1:0
WithP1Hand: LAW_040

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:LAW_040
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# CreditDefeatOffer_BothPlayersPools
#// COVERAGE: offer=CreditDefeatOffer_BothPlayersPools + ExpRecipientOffer_ExcludesTaramyn (both pending
#//           pools asserted) · decline=MayDeclineCreditDefeat_NoExp (PASS on the "you may defeat") ·
#//           boundary=DefeatFriendlyCredit_DualExp + NobodyHasCredits_NoExp (1-credit vs 0-credit
#//           boundary; "if you do" gate off) · control=DefeatCreditDualExp (defeating the OPPONENT's
#//           Credit grants the Experience to P1's side) · reqboundary=credit pick and recipient pick
#//           resolve in separate requests after the play request in every multi-answer section
#// LAW_040 Taramyn Barcona — "defeat a Credit token (belonging to any player)" offers BOTH players'
#// Credits in one pool: P1's Credit (seated after its 5 real resources) and P2's lone Credit. The
#// decision is left pending so the offer itself is the assertion.

## GIVEN
CommonSetup: bgw/bgw/{myResources:5;theirResources:0}
WithP1Credits: 1
WithP2Credits: 1
WithP1GroundArena: SEC_080:1:0
WithP1Hand: LAW_040

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:DONE

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myResources-5&theirResources-0

---

# ExpRecipientOffer_ExcludesTaramyn
#// LAW_040 Taramyn Barcona — after the Credit is defeated, the second Experience token goes to "another
#// friendly unit": the pick offers P1's SEC_080 (ground) and SOR_237 (space) but NOT Taramyn himself
#// (he gets his token automatically). The recipient decision is left pending so the pool is the
#// assertion.

## GIVEN
CommonSetup: bgw/bgw/{myResources:5;theirResources:0}
WithP2Credits: 1
WithP1GroundArena: SEC_080:1:0
WithP1SpaceArena: SOR_237:1:0
WithP1Hand: LAW_040

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirResources-0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0

---

# ExpRecipientChosen_SpaceUnit
#// LAW_040 Taramyn Barcona — resolving the recipient pick: P1 defeats P2's Credit and gives the second
#// Experience to the SPACE unit. Taramyn and SOR_237 each end with one Experience; the un-chosen
#// SEC_080 gets nothing and the recipient pick is mandatory (answered, not declined).

## GIVEN
CommonSetup: bgw/bgw/{myResources:5;theirResources:0}
WithP2Credits: 1
WithP1GroundArena: SEC_080:1:0
WithP1SpaceArena: SOR_237:1:0
WithP1Hand: LAW_040

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirResources-0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:LAW_040
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2CREDITCOUNT:0
