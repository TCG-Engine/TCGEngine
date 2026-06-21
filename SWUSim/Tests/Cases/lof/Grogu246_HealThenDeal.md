# LOF_246 Grogu — Hidden + Action [Exhaust]: heal up to 2 from a unit. If you do, deal that much to a
# unit. P1 heals 2 from its damaged SOR_046 (2 → 0) and deals 2 to the enemy SEC_080.

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_246:1:0
WithP1GroundArena: SOR_046:1:2
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:2
