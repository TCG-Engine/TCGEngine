# UpgradedPassive_OnAttackPoe
#// JTL_147 Black One — While upgraded, +1/+0; On Attack: if you control Poe Dameron, may deal 1 to a unit.
#// Upgraded (SOR_069) Black One has power 3 and, with Poe as leader, deals 1 to SOR_046 on attack, then
#// hits the enemy base for 3.

## GIVEN
CommonSetup: brw/bbk/{
  myLeader:JTL_013;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_147:1:0
WithP1SpaceArenaUpgrade: 0:SOR_069
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:3
P2GROUNDARENAUNIT:0:DAMAGE:1
