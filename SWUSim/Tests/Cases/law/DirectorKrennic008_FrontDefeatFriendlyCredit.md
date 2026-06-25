# LAW_008 Director Krennic (leader front) — "Action [Exhaust, defeat a friendly unit]: Create a Credit
# token." P1's only friendly unit (SEC_080) is defeated as the cost and 1 Credit is created.

## GIVEN
CommonSetup: ygk/grw/{
  myLeader:LAW_008;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:0
P1CREDITCOUNT:1
