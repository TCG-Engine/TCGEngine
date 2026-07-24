# WhenPlayed_ExpTwoOfficials
#// SEC_084 Mas Amedda (Ground, 3/4, Command/Villainy) — When Played: give an Experience token to each of
#//   up to 2 OTHER Official units. (Plot auto.) Two friendly Official units (SEC_041) each get +1/+1.

## GIVEN
CommonSetup: ggk/rrk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SEC_041:1:0
WithP1GroundArena: SEC_041:1:0
WithP1Hand: SEC_084

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1NODECISION

---

# WhenPlayed_ChooseNothing_NoTokens
#// SEC_084 Mas Amedda — the When Played grant is "up to 2", so P1 may choose NONE. With one other
#// friendly Official (SEC_041) in play, P1 declines → no Experience token is attached.

## GIVEN
CommonSetup: ggk/rrk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SEC_041:1:0
WithP1Hand: SEC_084

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION

---

# PlayedViaPlot_GrantsTokens
#// SEC_084 Mas Amedda — has Plot ("When you deploy a leader, you may play this card from your resources").
#// P1 holds Mas as a resource (myResources-0) plus 5 Command/Villainy resources. Deploying P1's leader opens
#// the Plot window; P1 plays Mas from resources and its When Played still grants Experience to up to 2 other
#// Officials — here both friendly SEC_041 units.

## GIVEN
CommonSetup: ggk/rrk
P1OnlyActions: true
WithP1Resources: 1:SEC_084:1,5:SEC_080:1
WithP1GroundArena: SEC_041:1:0
WithP1GroundArena: SEC_041:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1NODECISION
