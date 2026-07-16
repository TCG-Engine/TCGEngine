# Deploy_DebuffExpiresBeforeRegroupSweep
#// SOR_004 Chirrut Îmwe — interaction of his HP-survival rule with the regroup ordering.
#// P2 deals 4 damage to the deployed Chirrut (3/5) with Open Fire, then shrinks him -2/-2 with
#// Make an Opening (effective HP 3 → no remaining HP). During the action phase he survives (immune).
#// At regroup the -2/-2 debuff is removed BEFORE the defeat sweep, so his HP is back to 5 and 5-4=1
#// remaining HP — Chirrut LIVES. (Targeting him at all relies on the deployed-leader ZoneSearch fix.)

## GIVEN
CommonSetup: gbw/brk/{
  myLeader:SOR_004;
  theirLeader:SOR_011;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 5
WithP2Resources: 6
WithP2Hand: SOR_172
WithP2Hand: SOR_076

## WHEN
- P1>DeployLeader
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>Pass
- P2>PlayHand:0
- P1>Pass
- P2>Pass

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_004
P1GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:HP:5
P1LEADER:DEPLOYED

---

# Deploy_DefeatedAtRegroup
#// SOR_004 Chirrut Îmwe — Deployed: he survives lethal combat damage during the action phase
#// (see Chirrut_Deploy_SurvivesLethalInActionPhase) but "during the regroup phase, if he has no
#// remaining HP, defeat him." After both players pass, RegroupPhaseStart defeats the over-damaged
#// Chirrut — he leaves the arena and the leader returns NOT deployed.

## GIVEN
CommonSetup: gbw/brw/{
  myLeader:SOR_004;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP2GroundArena: SOR_213:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:0
- P1>Pass

## EXPECT
P1GROUNDARENACOUNT:0
P1LEADER:NOTDEPLOYED

---

# Deploy_SurvivesLethalInActionPhase
#// SOR_004 Chirrut Îmwe — Deployed: "During the action phase, this unit isn't defeated by
#// having no remaining HP." Chirrut (3/5) attacks Syndicate Lackeys (5/4); he takes 5 combat
#// damage (HP 5 → no remaining HP) but SURVIVES because it is still the action phase.

## GIVEN
CommonSetup: gbw/brw/{
  myLeader:SOR_004;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP2GroundArena: SOR_213:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_004
P1GROUNDARENAUNIT:0:DAMAGE:5
P1LEADER:DEPLOYED
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# Deployed_DiesToTakedown
#// SOR_004 Chirrut Îmwe — Deployed: he survives lethal combat damage during the action phase
#// (see Chirrut_Deploy_SurvivesLethalInActionPhase) but "during the regroup phase, if he has no
#// remaining HP, defeat him." After both players pass, RegroupPhaseStart defeats the over-damaged
#// Chirrut — he leaves the arena and the leader returns NOT deployed.

## GIVEN
CommonSetup: gbw/bbw/{
  myLeader:SOR_004;
  theirLeader:SOR_004:1:1:1:7;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SOR_077

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2LEADER:NOTDEPLOYED

---

# LeaderAction_BuffUnit
#// SOR_004 Chirrut Îmwe — Leader Action [Exhaust]: Give a unit +0/+2 for this phase.
#// One friendly unit on board → auto-targets it; HP rises by 2 (power unchanged), leader exhausts.

## GIVEN
CommonSetup: gbw/brw/{
  myLeader:SOR_004;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:5
P1LEADER:EXHAUSTED
