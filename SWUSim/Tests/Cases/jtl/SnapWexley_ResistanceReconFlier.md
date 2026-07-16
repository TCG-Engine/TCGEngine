# AsUnit_NextResistanceDiscount
#// JTL_098 Snap Wexley — "When played as a unit/On Attack: The next Resistance card you play this phase
#// costs 1 resource less." Played as a unit (no friendly Vehicle → no Pilot option), then P1 plays the
#// Resistance unit JTL_099 (cost 3) which costs 2 thanks to the discount. Resource check: 10 − 3 (Snap)
#// − 2 (discounted JTL_099) = 5 ready left (would be 4 without the discount).

## GIVEN
CommonSetup: ggw/rrk/{myResources:10;handCardIds:JTL_098}
P1OnlyActions: true
WithP1Hand: JTL_099

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1RESAVAILABLE:5

---

# AsUpgrade_SearchResistance
#// JTL_098 Snap Wexley — Piloting + "When played as an upgrade: Search the top 5 cards of your deck for a
#// Resistance card, reveal it, and draw it." Played as a Pilot onto SOR_237, P1 searches the top 5 (only
#// JTL_099 is a Resistance card) and draws it.

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;handCardIds:JTL_098}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1Deck: JTL_099
WithP1Deck: SEC_080
WithP1Deck: SOR_128
WithP1Deck: SOR_225
WithP1Deck: SOR_046

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:JTL_099

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1HANDCOUNT:1
