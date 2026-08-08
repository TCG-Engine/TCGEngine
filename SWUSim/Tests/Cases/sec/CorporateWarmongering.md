# BuffChosenAndOthers
#// SEC_091 Corporate Warmongering (event, cost 4) — Give a friendly unit +3/+3 for this phase; give
#//   each other friendly unit +1/+1 for this phase. P1 picks SEC_041 (1/4 → 4/7); SEC_042 (2/2 → 3/3)
#//   gets the +1/+1.

## GIVEN
CommonSetup: ggk/rrk/{myResources:4}
WithActivePlayer: 1
WithP1GroundArena: SEC_041:1:0
WithP1GroundArena: SEC_042:1:0
WithP1Hand: SEC_091

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:7
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:1:HP:3

---

# ChosenUnitMayBeInSpace_GroundAllyStillGetsThePlusOne
#// SEC_091 Corporate Warmongering — "a friendly unit" is arena-agnostic. P1 picks the SPACE unit
#// JTL_069 (+3/+3) and the ground SEC_041 still collects the "each other friendly unit" +1/+1, showing
#// the aura crosses arenas in both directions.

## GIVEN
CommonSetup: ggk/rrk/{myResources:4}
WithActivePlayer: 1
WithP1SpaceArena: JTL_069:1:0
WithP1GroundArena: SEC_041:1:0
WithP1Hand: SEC_091

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:5

---

# ChosenUnitMayBeADeployedLeaderUnit
#// SEC_091 Corporate Warmongering — a deployed leader is a unit, so it is a legal target for the +3/+3
#// as well as for the "each other friendly unit" half. P1's deployed leader takes the +3/+3 while the
#// ordinary SEC_041 gets +1/+1.

## GIVEN
CommonSetup: ggk/rrk/{myResources:4;myLeaderDeployed:true}
WithActivePlayer: 1
WithP1GroundArena: SEC_041:1:0
WithP1Hand: SEC_091

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:5
