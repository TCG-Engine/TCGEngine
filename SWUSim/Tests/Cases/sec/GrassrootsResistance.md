# Deal3Heal3
#// SEC_258 Grassroots Resistance (Event, Heroism, cost 4) — "Deal 3 to a unit. Heal 3 from your base."
#//   P1 base (3 damage) healed to 0; enemy SOR_046 takes 3.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4;myBaseDamage:3}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_258

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1BASEDMG:0
P2GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION
