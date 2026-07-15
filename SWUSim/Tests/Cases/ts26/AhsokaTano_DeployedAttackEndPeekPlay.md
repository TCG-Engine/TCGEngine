# TS26_008 Ahsoka Tano (leader deployed, 3/6) — Raid 1 + When Attack Ends: look at the top card; play it
# (costs 1 less), discard it, or leave it. Deployed Ahsoka attacks the enemy base (Raid 1 → 4), then plays
# SEC_080 from the top of the deck.
## GIVEN
CommonSetup: yyw/rrk/{myLeader:TS26_008:1:1;myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: [SEC_080 SOR_095]
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Play
## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:1:CARDID:SEC_080
