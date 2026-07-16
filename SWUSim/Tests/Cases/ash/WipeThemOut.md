# AttackExcessToAnotherUnit
#// ASH_137 Wipe Them Out (Event, cost 2) — Attack with a unit. For this attack, you may deal its excess
#// damage to another unit in the same arena. SOR_046 (3/7) attacks SOR_128 (3/1): 3 damage defeats it with
#// 2 excess; the player deals the 2 excess to the friendly SOR_095 (a unit in the same arena). SOR_046
#// survives the 3 counter.
## GIVEN
CommonSetup: ggk/ggk/{myResources:2;handCardIds:ASH_137}
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:DAMAGE:2
