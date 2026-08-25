# WhenPlayed_NameAndDiscard
#// SEC_186 Garindan (Ground, 1/3, cost 2) — When Played: name a card; look at an opponent's hand and
#//   discard a card with that name from it. P1 names Battlefield Marine; P2's SOR_095 is discarded.

## GIVEN
CommonSetup: yyk/rrk/{myResources:2}
P1OnlyActions: true
WithP2Hand: SOR_095
WithP1Hand: SEC_186

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Battlefield Marine
- P1>AnswerDecision:OK

## EXPECT
P2HANDCOUNT:0
P1GROUNDARENACOUNT:1

---

# WhenPlayed_NameNotInHand_NoDiscard
#// SEC_186 Garindan — if the named card is not in the opponent's hand, nothing is discarded. P1 names
#//   "Wampa" but P2's hand holds only SOR_095 (Battlefield Marine) → no card matches, so P2 keeps its
#//   card and Garindan simply enters play.

## GIVEN
CommonSetup: yyk/rrk/{myResources:2}
P1OnlyActions: true
WithP2Hand: SOR_095
WithP1Hand: SEC_186

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Wampa
- P1>AnswerDecision:OK

## EXPECT
P2HANDCOUNT:1
P1GROUNDARENACOUNT:1
P1NODECISION

---

# WhenPlayed_OpponentHandEmpty_NoPrompt
#// SEC_186 Garindan — with the opponent's hand empty there is nothing to look at or discard, so the
#//   naming is meaningless and the ability is skipped entirely (no NAMECARD prompt). Garindan just enters.

## GIVEN
CommonSetup: yyk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SEC_186

## WHEN
- P1>PlayHand:0

## EXPECT
P2HANDCOUNT:0
P1GROUNDARENACOUNT:1
P1NODECISION

---

# PlayedViaPlot_StillNamesAndDiscards
#// SEC_186 Garindan — he carries Plot, so he can be played from the resource row when a leader deploys,
#// and his When Played resolves exactly as from hand: P1 names Battlefield Marine and P2's copy is
#// discarded. The played card is replaced from the top of P1's deck, so the resource row holds at 6.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1Resources: 1:SEC_186:1,5:SOR_046:1
WithP1Deck: [SOR_095 SOR_095]
WithP2Hand: SOR_095

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:Battlefield Marine
- P1>AnswerDecision:OK

## EXPECT
P1LEADER:DEPLOYED
P2HANDCOUNT:0
P2DISCARDCOUNT:1
P1RESCOUNT:6

---

# TwinSuns_PicksTheSeatBEFORENamingTheCard
#// ⚠ THE SEAT-COUNT CELL — added 2026-08-24. "Name a card. Look at AN OPPONENT's hand and discard a card
#// with that name from it."
#// ⚠ ORDER: the opponent is picked FIRST, before the NAMECARD. You cannot look at "an opponent's" hand
#// until one is named, and naming blind at a hand you have not chosen is a different (worse) action.
#// The 2-player prompt sequence is unchanged because the picker auto-resolves invisibly.
#// ⚠ FILTER to opponents holding a card — an empty hand has nothing to look at or discard, which is the
#//   skip this card already had.
#// Seats 2 and 3 hold cards; SEAT 4 IS EMPTY-HANDED and must NOT be offered.
#// Mutation check: drop the SWUOpponentsWithCards filter and P1OPTIONNOT:P4 reds.

## GIVEN
CommonSetup: yyk/rrk/{myResources:2}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1Hand: SEC_186
WithP2Hand: SOR_095
WithP3Hand: SOR_095
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1HASDECISION
P1OPTIONHAS:P2
P1OPTIONHAS:P3
P1OPTIONNOT:P4
P1OPTIONNOT:P1
