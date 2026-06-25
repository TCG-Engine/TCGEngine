# LAW_011 Darth Vader (leader front) — "Action [Exhaust, discard a card from your hand]: Deal 1 damage
# to a unit or base." P1 discards SEC_080 (cost) and deals 1 to P2's SOR_128 (3/1), defeating it.

## GIVEN
CommonSetup: yrk/grw/{
  myLeader:LAW_011;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SEC_080
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1HANDCOUNT:0
P1DISCARDCOUNT:1
