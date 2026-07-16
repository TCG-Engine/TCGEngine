# IndirectPerVehicle
#// JTL_116 Dornean Gunship — When Played: deal indirect damage to a player equal to the number of Vehicle
#// units you control. P1 controls the Gunship (Vehicle) + SOR_237 = 2 Vehicles → 2 indirect to P2's base.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_116
WithP1Resources: 16
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent

## EXPECT
P2BASEDMG:2
