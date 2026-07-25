#// Leader-action CR-availability (CR 6.4.587.c): an action ability is usable whenever paying its
#// cost changes the game state, even if the effect can do nothing (no target). Each section below
#// uses a front-leader Action with NO valid effect target and asserts the leader still EXHAUSTS (the
#// cost is paid) and the game doesn't fault. Cost-requirement and activation-condition gates are
#// unaffected (SEC_007 Dryden still needs a 6+ card to DISCARD as part of its cost).
# SEC004_Leia_NoDisclosableCard
## GIVEN
CommonSetup: bbk/bbk/{myLeader:SEC_004;myBase:JTL_019;theirBase:SOR_021;myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095]
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1LEADER:EXHAUSTED
---
# SEC005_Satine_NoDamagedUnit
## GIVEN
CommonSetup: bbk/bbk/{myLeader:SEC_005;myBase:JTL_019;theirBase:SOR_021;myResources:5}
SkipPreGame: true
P1OnlyActions: true
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1LEADER:EXHAUSTED
---
# SEC010_Dedra_NoEnemyUnit
## GIVEN
CommonSetup: bbk/bbk/{myLeader:SEC_010;myBase:JTL_019;theirBase:SOR_021;myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095]
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1LEADER:EXHAUSTED
---
# SEC011_Pryce_NoExhaustedToken
## GIVEN
CommonSetup: bbk/bbk/{myLeader:SEC_011;myBase:JTL_019;theirBase:SOR_021;myResources:5}
SkipPreGame: true
P1OnlyActions: true
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1LEADER:EXHAUSTED
---
# SHD006_Jabba_NoUnit
## GIVEN
CommonSetup: bbk/bbk/{myLeader:SHD_006;myBase:JTL_019;theirBase:SOR_021;myResources:5}
SkipPreGame: true
P1OnlyActions: true
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1LEADER:EXHAUSTED
---
# SHD017_Lando_NoSmuggleTarget
## GIVEN
CommonSetup: bbk/bbk/{myLeader:SHD_017;myBase:JTL_019;theirBase:SOR_021;myResources:5}
SkipPreGame: true
P1OnlyActions: true
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1LEADER:EXHAUSTED
---
# TS2607_Asajj_NoTokenUnit
## GIVEN
CommonSetup: bbk/bbk/{myLeader:TS26_07;myBase:JTL_019;theirBase:SOR_021;myResources:5}
SkipPreGame: true
P1OnlyActions: true
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1LEADER:EXHAUSTED
---
# LAW001_Saw_NoUnit
## GIVEN
CommonSetup: bbk/bbk/{myLeader:LAW_001;myBase:JTL_019;theirBase:SOR_021;myResources:5}
SkipPreGame: true
P1OnlyActions: true
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1LEADER:EXHAUSTED
---
# LAW002_Beckett_NoUnit
## GIVEN
CommonSetup: bbk/bbk/{myLeader:LAW_002;myBase:JTL_019;theirBase:SOR_021;myResources:5}
SkipPreGame: true
P1OnlyActions: true
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1LEADER:EXHAUSTED
---
# LAW003_Kallus_NoPlayableCard
## GIVEN
CommonSetup: bbk/bbk/{myLeader:LAW_003;myBase:JTL_019;theirBase:SOR_021;myResources:5}
SkipPreGame: true
P1OnlyActions: true
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1LEADER:EXHAUSTED
---
# LAW004_Aurra_NoLowHpUnit
## GIVEN
CommonSetup: bbk/bbk/{myLeader:LAW_004;myBase:JTL_019;theirBase:SOR_021;myResources:5}
SkipPreGame: true
P1OnlyActions: true
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1LEADER:EXHAUSTED
---
# LAW006_Vel_NoUnit
## GIVEN
CommonSetup: bbk/bbk/{myLeader:LAW_006;myBase:JTL_019;theirBase:SOR_021;myResources:5}
SkipPreGame: true
P1OnlyActions: true
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1LEADER:EXHAUSTED
---
# LAW010_Leia_NoUnit
## GIVEN
CommonSetup: bbk/bbk/{myLeader:LAW_010;myBase:JTL_019;theirBase:SOR_021;myResources:5}
SkipPreGame: true
P1OnlyActions: true
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1LEADER:EXHAUSTED
---
# LAW012_Sebulba_NoUnit
## GIVEN
CommonSetup: bbk/bbk/{myLeader:LAW_012;myBase:JTL_019;theirBase:SOR_021;myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095]
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1LEADER:EXHAUSTED
---
# TWI005_Dooku_NoSeparatist
## GIVEN
CommonSetup: bbk/bbk/{myLeader:TWI_005;myBase:JTL_019;theirBase:SOR_021;myResources:5}
SkipPreGame: true
P1OnlyActions: true
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1LEADER:EXHAUSTED
---
# TWI009_Maul_NoUnit
## GIVEN
CommonSetup: bbk/bbk/{myLeader:TWI_009;myBase:JTL_019;theirBase:SOR_021;myResources:5}
SkipPreGame: true
P1OnlyActions: true
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1LEADER:EXHAUSTED
---
# TWI012_Anakin_NoUnit
## GIVEN
CommonSetup: bbk/bbk/{myLeader:TWI_012;myBase:JTL_019;theirBase:SOR_021;myResources:5}
SkipPreGame: true
P1OnlyActions: true
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1LEADER:EXHAUSTED
---
# TWI013_Mace_NoDamagedEnemy
## GIVEN
CommonSetup: bbk/bbk/{myLeader:TWI_013;myBase:JTL_019;theirBase:SOR_021;myResources:5}
SkipPreGame: true
P1OnlyActions: true
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1LEADER:EXHAUSTED
---
# TWI014_Asajj_NoUnit
## GIVEN
CommonSetup: bbk/bbk/{myLeader:TWI_014;myBase:JTL_019;theirBase:SOR_021;myResources:5}
SkipPreGame: true
P1OnlyActions: true
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1LEADER:EXHAUSTED
---
# TWI015_Grievous_NoDroid
## GIVEN
CommonSetup: bbk/bbk/{myLeader:TWI_015;myBase:JTL_019;theirBase:SOR_021;myResources:5}
SkipPreGame: true
P1OnlyActions: true
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1LEADER:EXHAUSTED
---
# SEC007_Dryden_Has6ButNoPlayableUnit
## GIVEN
CommonSetup: bbk/bbk/{myLeader:SEC_007;myBase:JTL_019;theirBase:SOR_021;myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_038
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1LEADER:EXHAUSTED
