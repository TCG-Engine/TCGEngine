# UpgradePlayed_Attack
#// JTL_202 Black Squadron Scout Wing — When you play an upgrade on this unit, you may attack with it
#// (+1/+0). P1 plays the vanilla upgrade SOR_069 onto JTL_202 (power 4), accepts, and it attacks the
#// enemy base for 4+1=5.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_069
WithP1Resources: 5
WithP1SpaceArena: JTL_202:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:5
P1SPACEARENAUNIT:0:EXHAUSTED
