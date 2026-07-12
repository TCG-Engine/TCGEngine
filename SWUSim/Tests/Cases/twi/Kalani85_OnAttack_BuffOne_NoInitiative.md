# TWI_085 Kalani (Unit 5/7, Ground) — "On Attack: You may choose another unit. If you have the
# initiative, you may choose up to 2 other units instead. Give each chosen unit +2/+2 for this phase."
# Without the initiative (P2 holds it), Kalani attacks P2's base and buffs 1 other unit (SOR_095 → 5/5).

## GIVEN
CommonSetup: ggw/rrk/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_085:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:1:HP:5
