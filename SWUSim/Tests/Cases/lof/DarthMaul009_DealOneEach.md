# LOF_009 Darth Maul — Action [Exhaust, use the Force]: Deal 1 damage to a unit and 1 damage to a different
# unit. Both enemy units take 1; P1 loses the Force.

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:LOF_009;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_059:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:1:DAMAGE:1
P1NOFORCE
