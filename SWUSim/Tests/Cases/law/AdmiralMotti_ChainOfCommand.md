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
