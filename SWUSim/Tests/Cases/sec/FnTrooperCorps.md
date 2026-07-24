# WhenPlayed_ExpAnother
#// SEC_243 FN Trooper Corps (Ground, 4/5, Villainy, cost 5) — When Played: give an Experience token to
#//   another friendly unit. (Plot auto.)

## GIVEN
CommonSetup: rrk/grw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_243

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION

---

# PlayViaPlot_ExpAnother
#// SEC_243 FN Trooper Corps — the When Played "give Experience to another friendly unit" still fires when
#//   FN Trooper Corps is played from resources via PLOT on a leader deploy. FN sits in P1's resources; on
#//   deploying the leader, Plot offers to play it; its When Played then gives Experience to the already-in-
#//   play friendly SOR_046 (it cannot target itself).

## GIVEN
CommonSetup: rrk/grw
P1OnlyActions: true
WithP1Resources: 1:SEC_243:1,7:SOR_095:1
WithP1GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
