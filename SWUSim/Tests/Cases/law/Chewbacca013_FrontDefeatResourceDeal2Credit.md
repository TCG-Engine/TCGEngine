# LAW_013 Chewbacca (leader front) — "Action [1 resource, Exhaust, defeat a friendly resource]: Deal 2
# damage to a unit and create a Credit token." Pay 1 + defeat a resource → 1 Credit created and 2 damage
# to P2's SOR_128 (3/1), defeating it.

## GIVEN
P1LeaderBase: LAW_013/SOR_028
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myResources-0

## EXPECT
P2GROUNDARENACOUNT:0
P1CREDITCOUNT:1
