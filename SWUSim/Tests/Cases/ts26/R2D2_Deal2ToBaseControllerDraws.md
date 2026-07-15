# TS26_062 R2-D2 (Unit 1/3, cost 2) — Raid 2 + When Played: you may deal 2 damage to a base; if you do,
# that base's controller draws a card. Dealing 2 to the enemy base makes P2 (its controller) draw.
## GIVEN
CommonSetup: rrw/rrk/{myResources:2;handCardIds:TS26_062}
WithP2Deck: [SEC_080 SOR_095]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
## EXPECT
P2BASEDMG:2
P2HANDCOUNT:1
