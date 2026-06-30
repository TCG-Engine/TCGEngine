# JTL_191 Invincible — the unique-Separatist discount is satisfied by a unique Separatist LEADER alone
# (no units in play). P1's leader is JTL_014 Admiral Trench (Separatist, unique, undeployed). The cost-6
# Invincible plays for 5, so 5 resources → 0 left. (Invincible is itself Separatist+unique, but the cost
# check runs before it enters play and only counts units already in play, so it can't self-satisfy.)

## GIVEN
CommonSetup: byk/bbk/{
  myLeader:JTL_014;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_191
WithP1Resources: 5

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_191
P1RESAVAILABLE:0
