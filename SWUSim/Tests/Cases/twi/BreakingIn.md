# AttackSaboteurBonus
#// TWI_224 Breaking In (Event, cost 2, Cunning) — "Attack with a unit. It gets +2/+0 and gains Saboteur
#// for this attack." SOR_095 attacks past P2's Sentinel (SOR_063) — Saboteur ignores Sentinel — straight
#// at the base, dealing 3+2 = 5. The Sentinel is untouched.

## GIVEN
CommonSetup: yyk/grw/{myResources:2;handCardIds:TWI_224}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:5
P2GROUNDARENAUNIT:0:DAMAGE:0
