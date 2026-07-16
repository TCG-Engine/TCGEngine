# HandCardIdsAlias
#// New myhandCardIds / theirhandCardIds aliases seed each hand (legacy handCardIds / theirHandCardIds still work).

## GIVEN
CommonSetup: grw/grw/{myResources:2;myhandCardIds:SOR_095;theirhandCardIds:SOR_046,SOR_237}

## WHEN

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SOR_095
P2HANDCOUNT:2
P2HANDCARD:0:SOR_046
P2HANDCARD:1:SOR_237

---

# LeaderDeployedAsPilot
#// myLeader override + myLeaderDeployedPilot: JTL_001 Asajj (pilot-capable leader) is deployed as a
#// Pilot upgrade onto P1's first friendly unit (the Vehicle host SOR_225 in space). The host becomes
#// a leader unit (CardLeaderCanDeployAsUpgrade), and the leader side reads Deployed.

## GIVEN
SkipPreGame: true
P1OnlyActions: true
CommonSetup: rrk/ggw/{myResources:6; myLeader:JTL_001; myLeaderDeployedPilot:1}
WithP1SpaceArena: SOR_225:1:0

## WHEN

## EXPECT
P1LEADER:DEPLOYED
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_001
P1SPACEARENAUNIT:0:ISLEADERUNIT

---

# LeaderDeployedAsUnit
#// myLeaderDeployed:true places the code's leader (rrk -> Vader SOR_010) as a REAL ground-arena
#// leader unit, linked via DeployedUniqueID — present, ready, and IsLeaderUnit on the board, not
#// just a Deployed flag. (It can attack like any unit; Vader's "On Attack: may deal 2" would need an
#// AnswerDecision step, so this demo asserts board presence instead.)

## GIVEN
CommonSetup: rrk/grw/{myResources:5;myLeaderDeployed:true}
P1OnlyActions: true

## WHEN

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_010
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1GROUNDARENAUNIT:0:READY
P1LEADER:DEPLOYED

---

# LeaderOverride
#// myLeader:SOR_005 overrides the code's natural leader (rrk would be Vader SOR_010) with any cardID.
#// Deployed as a unit to reveal the override on the board.

## GIVEN
CommonSetup: rrk/grw/{myResources:5;myLeader:SOR_005;myLeaderDeployed:true}
P1OnlyActions: true

## WHEN

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_005
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1LEADER:DEPLOYED

---

# MultilineOptsBlock
#// A multi-line opts block inside { } parses identically to the inline form (brace-folding parser).

## GIVEN
CommonSetup: grw/grw/{
  myResources:5;
  theirResources:3;
  myhandCardIds:SOR_095,SOR_046;
  theirhandCardIds:SOR_237
}

## WHEN

## EXPECT
P1RESCOUNT:5
P2RESCOUNT:3
P1HANDCOUNT:2
P1HANDCARD:0:SOR_095
P1HANDCARD:1:SOR_046
P2HANDCOUNT:1
P2HANDCARD:0:SOR_237
