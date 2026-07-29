# DeployedLeaderWithSpaceOverrideEntersSpaceArena
#// HMW_004's deployed side is The Death Star, a SPACE unit — the first leader whose deployed
#// arena differs from the default. Deploy must consult the leaderUnitArena override
#// (LeaderDeployArena), not the plain CardTargetArena default.

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004:0;myResources:9}

## WHEN
- P1>DeployLeader

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:HMW_004
P1GROUNDARENACOUNT:0
P1LEADER:DEPLOYED
P1LEADER:EPICUSED

---

# DeployedLeaderFixtureSeedsTheOverrideArena
#// The myLeaderDeployed FIXTURE must agree with a real deploy: it seeds the arena
#// LeaderDeployArena picks, not a hardcoded ground zone. Without this, any test that seeds a
#// deployed Tarkin would contradict the engine it is testing.

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004;myLeaderDeployed:true;myResources:9}
P1OnlyActions: true

## WHEN

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:HMW_004
P1SPACEARENAUNIT:0:ISLEADERUNIT
P1GROUNDARENACOUNT:0
P1LEADER:DEPLOYED

---

# DeployedLeaderHasDeployedSideTraits
#// The deployed side is a different printed face: The Death Star is an Imperial Vehicle Capital
#// Ship, NOT the leader side's Imperial Official. The leaderUnitTrait override REPLACES the
#// leader row's traits rather than adding to them.

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004;myLeaderDeployed:true;myResources:9}
P1OnlyActions: true

## WHEN

## EXPECT
P1SPACEARENAUNIT:0:HASTRAIT:Vehicle
P1SPACEARENAUNIT:0:HASTRAIT:Capital Ship
P1SPACEARENAUNIT:0:HASTRAIT:Imperial
P1SPACEARENAUNIT:0:NOTTRAIT:Official
