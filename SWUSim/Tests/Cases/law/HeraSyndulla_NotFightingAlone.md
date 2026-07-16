# PassiveHeroismCostWaive
#// LAW_009 Hera Syndulla (leader, passive) — "While you control 2 or more units, ignore the aspect
#// penalties on Heroism units you play." Hera is Command/Heroism (base Cunning); SOR_046 (Vigilance/
#// Heroism, cost 4) normally costs 4+2=6 (Vigilance off). With Hera + 2 controlled units, the penalty is
#// waived → it plays for 4 (exactly P1's resources).

## GIVEN
CommonSetup: ygw/grw/{
  myLeader:LAW_009;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SEC_080:1:0
WithP1Hand: SOR_046

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:3
P1RESAVAILABLE:0
