# MutualDamage
#// TWI_176 Caught in the Crossfire (Event, Aggression) — "Choose 2 enemy units in the same arena. Each deals
#// damage equal to its power to the other." SOR_046 (3/7) and SOR_095 (3/3): SOR_095 dies to 3, SOR_046 takes 3.
## GIVEN
CommonSetup: rrk/bbw/{myResources:6;handCardIds:TWI_176}
P1OnlyActions: true
WithP2GroundArena: [SOR_046:1:0 SOR_095:1:0]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-1
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3
