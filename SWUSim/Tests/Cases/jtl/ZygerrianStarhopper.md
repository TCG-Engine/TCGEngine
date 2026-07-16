# WhenDefeated_2Indirect
#// JTL_183 Zygerrian Starhopper — When Defeated: 2 indirect to a player. The pre-damaged Starhopper dies
#// attacking SOR_044, then deals 2 indirect which P2 assigns to its base.

## GIVEN
CommonSetup: ggk/ggk
WithActivePlayer: 1
WithP1SpaceArena: JTL_183:1:1
WithP2SpaceArena: SOR_044:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myBase-0:2

## EXPECT
P2BASEDMG:2
