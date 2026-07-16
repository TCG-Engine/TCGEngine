# LoseAbilities
#// JTL_244 There Is No Escape — Choose up to 3 units; they lose all abilities and can't gain abilities
#// this round. P1 targets the enemy SHD_147, which loses its Saboteur keyword.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_244
WithP1Resources: 6
WithP2GroundArena: SHD_147:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:NOTKEYWORD:Saboteur
