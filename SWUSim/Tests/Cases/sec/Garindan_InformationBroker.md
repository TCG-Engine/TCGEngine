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
