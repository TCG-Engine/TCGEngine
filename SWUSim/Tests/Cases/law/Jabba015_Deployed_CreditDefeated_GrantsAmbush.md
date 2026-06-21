# LAW_015 Jabba (deployed) — Action: Play an Underworld unit; if you defeated a Credit while paying its
# cost, that unit gains Ambush this phase. Jabba plays SOR_247 (cost 2); the player defeats a Credit to
# pay 1 less (1 resource), so SOR_247 enters with Ambush and immediately attacks P2's SOR_247 for 2.
# (WHEN sequence refined via live TestSchemaStep probing.)

## GIVEN
P1LeaderBase: LAW_015:1:1:1/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Credits: 1
WithP1GroundArena: LAW_015:1:0
WithP1Hand: SOR_247
WithP2GroundArena: SOR_247:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myResources-2
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_247
P1GROUNDARENAUNIT:1:HASKEYWORD:Ambush
P2GROUNDARENAUNIT:0:DAMAGE:2
P1CREDITCOUNT:0
P1RESAVAILABLE:1
