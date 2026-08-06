# PlotCardPaysTowardItsOwnCost
#// Bug #925. A Plot card is still a RESOURCE at the moment it is played, so it may be exhausted
#//   toward its own cost — the Plot play site says exactly that ("the Plot card may exhaust itself
#//   toward its cost"). SWUExhaustResources exhausts the LOWEST-INDEX ready resources though, so a
#//   Plot card sitting late in the zone is never the one spent: the player pays the full cost out of
#//   OTHER resources and then loses the Plot slot to the top-of-deck replacement as well.
#//
#//   Board is game 3303's. P1 has 7 ready resources with SEC_034 Cad Bane (cost 5, Plot) LAST, at
#//   index 6. Deploying SEC_001 Chancellor Palpatine (Epic Action, 7+ resources) gives "the next card
#//   you play using Plot this phase costs 3 resources less", so Cad Bane costs 2.
#//
#//   Paying 2 with Cad Bane itself as one of them means exactly ONE other resource is exhausted.
#//   Afterwards: Cad Bane has left the zone, the top deck card enters EXHAUSTED (_SWUPlotReplaceSlot),
#//   so of 7 slots 5 are ready — 7 ready, minus Cad Bane leaving, minus the single other resource.
#//   Paying 2 from other resources instead leaves only 4 ready, which is the reported symptom.

## GIVEN
CommonSetup: ngw/ngw/{myLeader:SEC_001;myBase:JTL_024;theirBase:JTL_027}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 1:LAW_039:1,1:SEC_082:1,1:ASH_048:1,1:ASH_116:1,1:SEC_082:1,1:LAW_039:1,1:SEC_034:1
WithP1Deck: SOR_095,SOR_095

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myResources-6

## EXPECT
P1RESAVAILABLE:5

---

# PlotUpgradePaysTowardItsOwnCost_FromLateIndex
#// The UPGRADE half of bug #925, which the unit case above does not cover: an upgrade defers payment
#//   to ATTACH_UPGRADE, which re-encodes its call through DROID_PAY (arg parser capped at 5 fields).
#//   Any fix that threads a prepayment is silently dropped at that hop — reordering the resource zone
#//   before the play is not, which is why the fix lives in PLOT_PLAY.
#//
#//   Mirrors sec/ArmorOfFortune.md exactly except that SEC_070 (Upgrade, Plot, cost 2, +0/+3) sits
#//   LAST rather than first. Same expected outcome: 6 ready -> 4, i.e. 2 consumed including the card
#//   itself, resource COUNT unchanged (the slot is replaced from the top of the deck, exhausted).

## GIVEN
CommonSetup: bbk/grw
P1OnlyActions: true
WithP1GroundArena: SOR_095:1
WithP1Resources: 5:SOR_095:1,1:SEC_070:1
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myResources-5
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1RESCOUNT:6
P1RESAVAILABLE:4
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# PlotWithEmptyDeck_SlotIsLostNotReplaced
#// "Replace it with the top card of your deck" has nothing to replace with when the deck is EMPTY, so
#//   the resource slot is simply GONE — the player is down a resource permanently. _SWUPlotReplaceSlot
#//   loops the deck and appends nothing when it finds no card, which is the correct outcome; this pins
#//   it so a future "always append something" change can't quietly paper over the loss.
#//
#//   Same board as the first case (Cad Bane at index 6, cost 5 − 3 = 2) but with no deck. Self-pay still
#//   applies: the card plus ONE other resource are exhausted. 7 slots → 6 (no replacement), 5 ready.
#//   BASEDMG stays 0 — replacing a Plot slot is not a DRAW, so CR 6.1's empty-deck damage must not fire.

## GIVEN
CommonSetup: ngw/ngw/{myLeader:SEC_001;myBase:JTL_024;theirBase:JTL_027}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 1:LAW_039:1,1:SEC_082:1,1:ASH_048:1,1:ASH_116:1,1:SEC_082:1,1:LAW_039:1,1:SEC_034:1

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myResources-6

## EXPECT
P1RESCOUNT:6
P1RESAVAILABLE:5
P1BASEDMG:0
