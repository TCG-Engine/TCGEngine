# OnAttack_Decline_NoPing
#// JTL_001 pilot grant is "you may" — declining the friendly-unit pick does nothing (no self-ping,
#// the enemy TIE survives).

## GIVEN
CommonSetup: yrk/grw/{myResources:6;myLeader:JTL_001;myLeaderDeployedPilot:true}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENACOUNT:1

---

# OnAttack_PingsFriendlyAndEnemy
#// JTL_001 Asajj Ventress deployed as a PILOT — the host gains "On Attack: You may deal 1 to a
#// friendly unit; if you do, deal 1 to an enemy unit in the same arena." Host (SOR_237) attacks the
#// base; the grant pings the host (only friendly) for 1, then the same-arena enemy TIE (2/1) dies.

## GIVEN
CommonSetup: yrk/grw/{myResources:6;myLeader:JTL_001;myLeaderDeployedPilot:true}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:1
P2SPACEARENACOUNT:0

---

# DeployAsPilot_BecomesLeaderUnit
#// JTL_001 Asajj Ventress — Deploy as Pilot onto SOR_225 (TIE/ln Fighter, Space).
#// Deploy threshold = 6. SOR_225 base 2/1; JTL_001 upgradePower=3, upgradeHp=4 → host 5/5.
#// deployBox: "Attached unit is a leader unit. It gains Grit and: On Attack..."
#// One friendly Vehicle present → DeployLeader offers Unit/Pilot choice.
#// Player picks Pilot → auto-attaches to the single Vehicle (no MZCHOOSE).
#// After: host has JTL_001 as upgrade, power=5, hp=5, Grit, is a Leader Unit.
#// Leader: EpicActionUsed, Deployed. Resources unchanged (deploy is free).

## GIVEN
CommonSetup: gbk/gbk/{
  myLeader:JTL_001;
  myBase:SOR_022;
  theirLeader:JTL_001;
  theirBase:SOR_022
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1SpaceArena: SOR_225:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:Pilot

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_225
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_001
P1SPACEARENAUNIT:0:POWER:5
P1SPACEARENAUNIT:0:HP:5
P1SPACEARENAUNIT:0:HASKEYWORD:Grit
P1SPACEARENAUNIT:0:ISLEADERUNIT
P1GROUNDARENACOUNT:0
P1LEADER:DEPLOYED
P1LEADER:EPICUSED
P1LEADER:EXHAUSTED
P1RESAVAILABLE:6

---

# DeployNoVehicle_NormalDeploy
#// JTL_001 Asajj Ventress — No friendly Vehicle present.
#// With no eligible Vehicle, DeployLeader skips the Unit/Pilot choice and
#// deploys normally to the Ground Arena (no OPTIONCHOOSE, no decision pending).
#// Deploy threshold = 6.

## GIVEN
CommonSetup: gbk/gbk/{
  myLeader:JTL_001;
  myBase:SOR_022;
  theirLeader:JTL_001;
  theirBase:SOR_022
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6

## WHEN
- P1>DeployLeader

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_001
P1LEADER:DEPLOYED
P1LEADER:EPICUSED
P1NODECISION

---

# LeaderAction_DealsFriendlyThenEnemySameArena
#// JTL_001 Asajj Ventress (leader) — Action [Exhaust]: Deal 1 damage to a friendly unit. If you do,
#// deal 1 damage to an enemy unit in the same arena. P1's only friendly unit (SEC_080, ground) takes 1,
#// then the only enemy unit in the SAME (ground) arena (SOR_095) takes 1. Both auto-resolve (1 each).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:DAMAGE:1
P1LEADER:EXHAUSTED

---

# LeaderAction_NoEnemyInArena_OnlyFriendlyDamaged
#// JTL_001 Asajj Ventress (leader) — the enemy half is restricted to the SAME arena as the damaged
#// friendly unit. The friendly unit is in the GROUND arena; the only enemy unit is in the SPACE arena,
#// so after dealing 1 to the friendly there is no enemy to hit in the ground arena — the second half
#// fizzles. Proves the "in the same arena" clause.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:DAMAGE:1
P2SPACEARENAUNIT:0:CARDID:SOR_225
P2SPACEARENAUNIT:0:DAMAGE:0
P1LEADER:EXHAUSTED

---

# LeaderAction_NoFriendlyUnit_Fizzle
#// JTL_001 Asajj Ventress (leader) — with no friendly unit to damage, the whole ability fizzles
#// (you can't "deal 1 to a friendly", so the "if you do" enemy half never happens). The leader still
#// spends its action (exhausts), the enemy unit is untouched, and no decision is left pending.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:DAMAGE:0
P1LEADER:EXHAUSTED
P1NODECISION
