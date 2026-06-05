# JTL_227 Superheavy Ion Cannon (upgrade on a Capital Ship) — granted On Attack: may exhaust an enemy
# non-leader unit; if you do, deal indirect to the defending player equal to that unit's power. Host
# JTL_069 (Capital Ship, 4 power; JTL_227 grants +0 power) attacks P2's base. P1 exhausts P2's SEC_080
# (power 3) → 3 indirect, which P2 ASSIGNS across a unit AND the base: 1 to their 1-HP SOR_128 (defeats
# it) + 2 to their base. SEC_080 stays in play (exhausted, undamaged). P2 base = 4 combat + 2 indirect = 6.

## GIVEN
P1LeaderBase: JTL_001/SOR_020
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_069:1:0
WithP1SpaceArenaUpgrade: 0:JTL_227
WithP2SpaceArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirSpaceArena-0
- P2>AnswerDecision:myGroundArena-0:1,myBase-0:2

## EXPECT
P2GROUNDARENACOUNT:0
P2SPACEARENACOUNT:1
P2BASEDMG:6
P1NODECISION
