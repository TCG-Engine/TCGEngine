# NameCard_ExpPerCopy
#// SEC_260 Inspector's Shuttle (Space, 1/3, cost 2) — When Played: name a card; for each copy of it in
#//   an opponent's hand, give an Experience token to this unit. P2 hand has 2 Battlefield Marines → +2/+2 → 3 power.

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP2Hand: SOR_095
WithP2Hand: SOR_095
WithP1Hand: SEC_260

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Battlefield Marine

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SEC_260
P1SPACEARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:0:UPGRADECOUNT:2
P1NODECISION

---

# NameCard_SameTitleDifferentSubtitle
#// SEC_260 Inspector's Shuttle — the count is by TITLE, so two different Millennium Falcon printings
#//   (SOR_193 and SHD_204) both count as copies of "Millennium Falcon". P2 hand also holds an unrelated
#//   Wampa. Naming Millennium Falcon reveals P2's hand and grants 2 Experience → 1 power becomes 3.

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP2Hand: SOR_193
WithP2Hand: SHD_204
WithP2Hand: SOR_164
WithP1Hand: SEC_260

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Millennium Falcon

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SEC_260
P1SPACEARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:0:UPGRADECOUNT:2
P1NODECISION

---

# WhenPlayed_OpponentHandEmpty_NoPrompt
#// SEC_260 Inspector's Shuttle — with the opponent's hand empty there is nothing to reveal or count, so
#//   the naming is skipped entirely (no NAMECARD prompt) and no Experience is granted. Enters as 1/3.

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SEC_260

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SEC_260
P1SPACEARENAUNIT:0:POWER:1
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION
