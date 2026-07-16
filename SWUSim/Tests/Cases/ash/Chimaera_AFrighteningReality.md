# DefeatTwoHealOnEnemyDefeat
#// ASH_052 Chimaera (Space, 6/6, cost 7) — When Played: you may choose a friendly unit and an enemy
#// non-leader unit; if you do, defeat both. Plus: When an enemy unit is defeated, heal 2 from your base.
#// P1's base starts at 3 damage; playing Chimaera defeats friendly SOR_095 and enemy SEC_080, and the
#// enemy defeat heals 2 (3 → 1).
## GIVEN
CommonSetup: bbk/bbk/{myResources:7;handCardIds:ASH_052;myBaseDamage:3}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1BASEDMG:1
