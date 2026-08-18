# FriendlyLeadersBuff
#// LAW_139 Admiral Motti (4/5) — Friendly leader units get +2/+2. Deploy Luke (4/7); with Motti he is 6/9.

## GIVEN
CommonSetup: bbw/bgw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: LAW_139:1:0

## WHEN
- P1>DeployLeader

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_005
P1GROUNDARENAUNIT:1:POWER:6
P1GROUNDARENAUNIT:1:HP:9

---

# BuffOnlyAppliesToFriendlyLeaderUnits
#// LAW_139 Admiral Motti — the +2/+2 is ONLY for friendly LEADER units. Motti himself (a non-leader unit)
#// stays 4/5, a friendly non-leader unit (SEC_080 Dark Trooper, 3/3) is unaffected, and the deployed
#// leader (Luke) becomes 6/9.

## GIVEN
CommonSetup: bbw/bgw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: LAW_139:1:0
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>DeployLeader

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_139
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:5
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:1:HP:3
P1GROUNDARENAUNIT:2:CARDID:SOR_005
P1GROUNDARENAUNIT:2:POWER:6
P1GROUNDARENAUNIT:2:HP:9

---

# EnemyLeaderUnitsAreNotBuffed
#// LAW_139 Admiral Motti — "FRIENDLY leader units get +2/+2". The existing sections only ever show the
#// buff landing (P1's own deployed leader) or missing on non-leaders; neither seats an enemy leader, so
#// neither could catch an aura that had dropped the controller filter. Here P1's Motti and P1's deployed
#// Luke share a board with P2's deployed Darth Vader: Luke is 4/7 -> 6/9 and Vader must stay a printed
#// 5/8.

## GIVEN
CommonSetup: bbw/bgw/{myResources:6; theirLeader:SOR_010:1:1:1}
P1OnlyActions: true
WithP1GroundArena: LAW_139:1:0

## WHEN
- P1>DeployLeader

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_005
P1GROUNDARENAUNIT:1:POWER:6
P1GROUNDARENAUNIT:1:HP:9
P2GROUNDARENAUNIT:0:CARDID:SOR_010
P2GROUNDARENAUNIT:0:POWER:5
P2GROUNDARENAUNIT:0:HP:8

---

# BuffDisappearsWhenMottiLeavesPlay
#// LAW_139 Admiral Motti — the +2/+2 is a continuous aura from a unit in play, not a one-shot stamp
#// applied at deploy time, so it must vanish the moment Motti does. P1 deploys Luke (6/9 with Motti out),
#// then plays LAW_264 It's Worse to defeat its own Motti; Luke drops straight back to his printed 4/7.
#// Without this section an implementation that wrote the bonus onto the leader once would look identical
#// to a live aura in every other section here.

## GIVEN
CommonSetup: bbw/bgw/{myResources:9}
P1OnlyActions: true
WithP1GroundArena: LAW_139:1:0
WithP1Hand: LOF_264

## WHEN
- P1>DeployLeader
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_005
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:7
