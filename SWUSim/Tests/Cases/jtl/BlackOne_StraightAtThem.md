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

---

# NotUpgraded_NoBuff
#// JTL_147 Black One — the "+1/+0 while upgraded" is conditional. With no upgrade attached, Black One is at
#// its printed 2 power.

## GIVEN
CommonSetup: brw/bbk/{
  myLeader:JTL_013;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_147:1:0

## WHEN

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_147
P1SPACEARENAUNIT:0:POWER:2

---

# NoPoe_OnAttack_NoDamage
#// JTL_147 Black One — the On-Attack "deal 1 to a unit" requires you to control Poe Dameron. With a non-Poe
#// leader (JTL_001) and no Poe in play, the ability does not trigger: attacking the base deals 3 (upgraded)
#// and the enemy SOR_046 is not damaged (no decision is offered).

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_147:1:0
WithP1SpaceArenaUpgrade: 0:SOR_069
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1NODECISION
P2BASEDMG:3
P2GROUNDARENAUNIT:0:DAMAGE:0
