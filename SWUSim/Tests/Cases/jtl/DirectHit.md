# DefeatNonLeaderVehicle
#// JTL_078 Direct Hit (event) — Defeat a non-leader Vehicle unit. The only Vehicle (SOR_237) is defeated;
#// the non-Vehicle SEC_080 is not a legal target and survives.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_004;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_078
WithP1Resources: 4
WithP2SpaceArena: SOR_237:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2SPACEARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080

---

# CannotTargetLeaderVehicle
#// JTL_078 Direct Hit — "Defeat a NON-LEADER Vehicle unit." A Vehicle piloted by a deployed leader is a
#// LEADER unit and is NOT a legal target. P1's leader (JTL_001 Asajj) is deployed as a Pilot onto its
#// first friendly unit — the ground Vehicle SOR_232 (AT-ST) → that host becomes a LEADER unit. P1 also
#// controls a plain non-leader Vehicle SOR_225 (space). The only legal Direct Hit target is the non-leader
#// SOR_225, which auto-resolves and is defeated; the leader-piloted AT-ST is excluded and survives with
#// its leader upgrade intact.

## GIVEN
SkipPreGame: true
P1OnlyActions: true
CommonSetup: rrk/ggw/{myResources:12; myLeader:JTL_001; myLeaderDeployedPilot:1}
WithP1Hand: JTL_078
WithP1SpaceArena: SOR_225:1:0
WithP1GroundArena: SOR_232:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_232
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1HANDCOUNT:0
