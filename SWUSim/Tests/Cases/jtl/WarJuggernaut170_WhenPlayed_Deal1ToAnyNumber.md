# JTL_170 War Juggernaut — When Played: Deal 1 damage to each of any number of units. P1 picks both
# enemy units (each takes 1). The two newly-damaged units also raise the Juggernaut's own power
# (3 + 2 damaged = 5), exercising the passive together with the AOE.

## GIVEN
P1LeaderBase: JTL_012/JTL_022
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_170
WithP1Resources: 6
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:1:DAMAGE:1
P1GROUNDARENAUNIT:0:CARDID:JTL_170
P1GROUNDARENAUNIT:0:POWER:5
