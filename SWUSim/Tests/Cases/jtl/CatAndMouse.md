# ExhaustEnemy_ReadyFriendly
#// JTL_195 Cat and Mouse (event) — Exhaust an enemy unit; if you do, ready a friendly unit in the same
#// arena with power <= that enemy unit's power. P1 exhausts the enemy SOR_046 (power 3) and readies the
#// friendly SOR_095 (power 3 <= 3).

## GIVEN
CommonSetup: gyk/bbk/{
  myLeader:JTL_015;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_195
WithP1Resources: 3
WithP1GroundArena: SOR_095:0:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:READY
