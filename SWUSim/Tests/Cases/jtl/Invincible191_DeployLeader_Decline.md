# JTL_191 Invincible — the deploy-leader bounce is a "may": declining leaves the eligible unit in play.
# Same setup as the take test; P1 declines the MZMAYCHOOSE, so P2's SOR_063 stays and P2's hand is empty.

## GIVEN
CommonSetup: byk/bbw/{
  myLeader:SOR_015;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1SpaceArena: JTL_191:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:-

## EXPECT
P1LEADER:DEPLOYED
P2GROUNDARENACOUNT:1
P2HANDCOUNT:0
