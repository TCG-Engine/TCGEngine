# RevealCostMatchDamage
#// ASH_132 Queen Soruna (Ground, 5/7, cost 6) — When Played: you may reveal a unit from your hand; if you
#// do, deal 3 damage to a unit with the same cost as the revealed unit. P1 plays Soruna, reveals SOR_237
#// (cost 2), then deals 3 to the only cost-2 unit, SEC_080 (3/3), defeating it.
## GIVEN
CommonSetup: ggk/ggk/{myResources:6;handCardIds:ASH_132,SOR_237}
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
## EXPECT
P2GROUNDARENACOUNT:0
