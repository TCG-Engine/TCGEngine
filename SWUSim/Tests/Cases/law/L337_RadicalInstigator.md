# WhenPlayedSearchPlayDroids
#// LAW_063 L3-37 (3/2, Hidden) — When Played: search the top 10 cards for any number of Droid units with
#// combined cost 5 or less and play each for free. Two SEC_080 (Droid, cost 2 each = 4) on top are both
#// played; SOR_237 (non-Droid) is left.

## GIVEN
CommonSetup: grw/bgw/{myResources:6}
WithP1Deck: SEC_080
WithP1Deck: SEC_080
WithP1Deck: SOR_237
WithP1Hand: LAW_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SEC_080,SEC_080

## EXPECT
P1GROUNDARENACOUNT:3
P1DECKCOUNT:1

---

# PlayedForFree_IgnoresTheASPECTPenalty
#// "play each of them for FREE" — free means 0, aspect penalty included. The grw/bgw setup covers
#// Command, Aggression and Heroism, so SEC_080 Imperial Dark Trooper (Command/VILLAINY) carries an
#// uncovered pip and would normally cost 2 + 2 = 4. It still lands.
#// L3-37 costs 6 out of 6, leaving 0: if the penalty were being charged the play could not happen at all,
#// which is what makes the resource assertion load-bearing rather than incidental.

## GIVEN
CommonSetup: grw/bgw/{myResources:6}
P1OnlyActions: true
WithP1Hand: LAW_063
WithP1Deck: [SEC_080 SOR_171 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SEC_080

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1RESAVAILABLE:0

---

# TakeNothing_DeckIsReturnedNotMilled
#// The decline branch: nothing is played and every peeked card goes back to the deck rather than being
#// milled. 10 seeded, 10 still there.

## GIVEN
CommonSetup: grw/bgw/{myResources:6}
P1OnlyActions: true
WithP1Hand: LAW_063
WithP1Deck: [SEC_080 SEC_080 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_063
P1DECKCOUNT:10

---

# PILOTingDroidIsPlayedAsAUNIT_NotAsAPilot
#// A Piloting card IS a unit card, so it is a legal find for "any number of DROID units" — but it is
#// played AS A UNIT. The ability named what it was searching for and plays that; the pilot-upgrade mode
#// is never offered, even with a legal Vehicle host on the board.
#// JTL_245 R2-D2 is a cost-1 Droid with "Piloting [0 resources Heroism]". SEC_214 Skyhopper Canyon Runner
#// is a friendly Vehicle, so a pilot host exists — drop it and this section passes for the wrong reason.
#// R2-D2 must end up in the ground arena as its own unit, with the Vehicle carrying no upgrade.

## GIVEN
CommonSetup: grw/bgw/{myResources:6}
P1OnlyActions: true
WithP1Hand: LAW_063
WithP1GroundArena: SEC_214:1:0
WithP1Deck: [JTL_245 SOR_171 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:JTL_245

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
