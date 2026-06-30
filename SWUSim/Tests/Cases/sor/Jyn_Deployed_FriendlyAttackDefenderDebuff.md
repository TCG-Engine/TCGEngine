# SOR_018 Jyn Erso — Deployed: "While a friendly unit is attacking, the defender gets -1/-0."
# Jyn is deployed (ground idx 1); a SEPARATE friendly 3/3 (idx 0) attacks P2's 3/7. The passive
# reduces the defender's power 3 → 2, so the attacker takes 2 counter-damage (3 without it).

## GIVEN
CommonSetup: gyw/brw/{
  myLeader:SOR_018;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:3
P1LEADER:DEPLOYED
