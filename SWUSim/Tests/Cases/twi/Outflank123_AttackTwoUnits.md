# TWI_123 Outflank (Event, Command) — "Attack with 2 units (one at a time)." Two SOR_095 (power 3) each
# attack the enemy base for 6 total.
## GIVEN
CommonSetup: ggw/rrk/{myResources:1;handCardIds:TWI_123}
P1OnlyActions: true
WithP1GroundArena: [SOR_095:1:0 SOR_095:1:0]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P2BASEDMG:6
