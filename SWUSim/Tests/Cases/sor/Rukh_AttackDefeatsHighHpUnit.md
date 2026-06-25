# SOR_085 Rukh (3/6) — "When this unit deals combat damage to a non-leader unit while attacking:
# Defeat that unit." Rukh attacks a 3/7 that would SURVIVE the 3 combat damage, but Rukh's ability
# defeats it anyway. Rukh takes 3 counter-damage and survives.

## GIVEN
CommonSetup: rrk/brw/{
  myLeader:SOR_011;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_085:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_085
P1GROUNDARENAUNIT:0:DAMAGE:3
