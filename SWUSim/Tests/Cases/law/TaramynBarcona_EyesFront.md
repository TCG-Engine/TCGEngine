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
