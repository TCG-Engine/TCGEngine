# SHD_007 Moff Gideon (front Action [Exhaust]) — "Attack with a unit that costs 3 or less. If it's
# attacking a unit, it gets +1/+0 for this attack." SOR_095 (cost 2, power 3) is the lone ≤3 attacker;
# it attacks the enemy SOR_046 and deals 3 + 1 = 4.

## GIVEN
CommonSetup: ggk/ggk/{myLeader:SHD_007}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
