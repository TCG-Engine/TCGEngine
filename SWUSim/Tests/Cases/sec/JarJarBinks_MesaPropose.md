# WhenPlayed_BuffAnother
#// SEC_111 Jar Jar Binks (Ground, 2/1, Command) — When Played: you may give another friendly unit
#//   +2/+2 for this phase. P1 plays Jar Jar and buffs SEC_041 (1/4 → 3/6).

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
WithActivePlayer: 1
WithP1GroundArena: SEC_041:1:0
WithP1Hand: SEC_111

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:6

---

# WhenPlayed_Decline
#// SEC_111 Jar Jar Binks — the When-Played buff is a "may". Declining leaves SEC_041 at its base 1/4.

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
WithActivePlayer: 1
WithP1GroundArena: SEC_041:1:0
WithP1Hand: SEC_111

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:POWER:1
P1GROUNDARENAUNIT:0:HP:4

---

# WhenPlayed_BuffExpiresNextPhase
#// SEC_111 Jar Jar Binks — the +2/+2 lasts only "for this phase". P1 plays Jar Jar and buffs SOR_095
#//   Battlefield Marine (3/3 → 5/5); the buffed Marine attacks the base for 5. After advancing to the
#//   next action phase the buff is gone and the Marine is back to its printed 3/3.

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_111
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AttackGroundArena:0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3

---

# PlayedViaPlot_BuffApplies
#// SEC_111 Jar Jar Binks — has Plot ("When you deploy a leader, you may play this card from your
#//   resources"). P1 holds Jar Jar as a resource (myResources-0) plus 5 filler resources. Deploying P1's
#//   leader opens the Plot window; P1 plays Jar Jar from resources and its When Played buff still fires,
#//   giving SOR_095 Battlefield Marine +2/+2 (3/3 → 5/5).

## GIVEN
CommonSetup: ggk/rrk
P1OnlyActions: true
WithP1Resources: 1:SEC_111:1,5:SEC_080:1
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P1NODECISION
