# SOR_088 Blizzard Assault AT-AT (9/9) — "When this unit attacks and defeats a unit: You may deal
# the excess damage from this attack to an enemy ground unit." It attacks a 3/3 (excess = 9-3 = 6),
# defeats it, then deals 6 to the opponent's other ground unit (a 3/7, which survives at 6 damage).

## GIVEN
CommonSetup: rrk/brw/{
  myLeader:SOR_011;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_088:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:6
P1GROUNDARENAUNIT:0:DAMAGE:3
