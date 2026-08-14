# ChooseGround_ExhaustsGround
#// SOR_221 Outmaneuver (Event) — Choose an arena (ground or space). Exhaust each unit in that
#// arena. P1 chooses the GROUND arena via the new option-picker; every ground unit (both
#// players) is exhausted, while the space units stay ready. Tested via AnswerDecision:Ground.
#// COVERAGE: offer=both option branches exercised (Ground in ChooseGround_ExhaustsGround,
#//           Space in ChooseSpace_ExhaustsSpaceOnly) and an EMPTY arena stays a legal option
#//           (ChooseSpace_EmptyArena_NoEffect) · decline=N/A (the arena choice is mandatory) ·
#//           control=N/A (exhaust is by arena membership, controller-agnostic — both players'
#//           units and both deployed leader units asserted in ChooseGround_ExhaustsDeployedLeaders)
#//           · boundary=occupied vs empty chosen arena (ChooseSpace_ExhaustsSpaceOnly vs
#//           ChooseSpace_EmptyArena_NoEffect) · reqboundary=every section (play and arena answer
#//           span separate requests)

## GIVEN
CommonSetup: yyk/yyk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SOR_221
WithP1GroundArena: SEC_080:1:0    # friendly ground (ready) → exhausted
WithP2GroundArena: SEC_080:1:0    # enemy ground (ready) → exhausted
WithP1SpaceArena: SOR_060:1:0     # friendly space (ready) → stays ready
WithP2SpaceArena: SOR_060:1:0     # enemy space (ready) → stays ready

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:EXHAUSTED
P1SPACEARENAUNIT:0:READY
P2SPACEARENAUNIT:0:READY

---

# ChooseSpace_ExhaustsSpaceOnly
#// SOR_221 Outmaneuver — choosing the SPACE arena exhausts every space unit (both players)
#// while ground units AND ground deployed leader units stay ready.

## GIVEN
CommonSetup: yyk/yyk/{myResources:5;myLeaderDeployed:true;theirLeaderDeployed:true}
P1OnlyActions: true
WithP1Hand: SOR_221
WithP1GroundArena: SEC_080:1:0    # friendly ground (ready) → stays ready
WithP2GroundArena: SEC_080:1:0    # enemy ground (ready) → stays ready
WithP1SpaceArena: SOR_060:1:0     # friendly space (ready) → exhausted
WithP2SpaceArena: SOR_060:1:0     # enemy space (ready) → exhausted

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Space

## EXPECT
P1SPACEARENAUNIT:0:EXHAUSTED
P2SPACEARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:ISLEADERUNIT
P1GROUNDARENAUNIT:1:READY
P2GROUNDARENAUNIT:1:ISLEADERUNIT
P2GROUNDARENAUNIT:1:READY

---

# ChooseGround_ExhaustsDeployedLeaders
#// SOR_221 Outmaneuver — deployed LEADER units are units in the arena: choosing GROUND
#// exhausts both players' deployed ground leaders; space units stay ready.

## GIVEN
CommonSetup: yyk/yyk/{myResources:5;myLeaderDeployed:true;theirLeaderDeployed:true}
P1OnlyActions: true
WithP1Hand: SOR_221
WithP1SpaceArena: SOR_060:1:0     # friendly space (ready) → stays ready
WithP2SpaceArena: SOR_060:1:0     # enemy space (ready) → stays ready

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground

## EXPECT
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:ISLEADERUNIT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1SPACEARENAUNIT:0:READY
P2SPACEARENAUNIT:0:READY

---

# ChooseSpace_EmptyArena_NoEffect
#// SOR_221 Outmaneuver — an EMPTY arena is still a legal choice: with no space units
#// anywhere, choosing Space simply does nothing (no hang, no ground exhausts).

## GIVEN
CommonSetup: yyk/yyk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SOR_221
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Space

## EXPECT
P1GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:0:READY
P1DISCARDCOUNT:1
P1NODECISION
