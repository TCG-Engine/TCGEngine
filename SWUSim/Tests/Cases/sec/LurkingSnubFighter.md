# WhenPlayed_Decline
#// SEC_189 — the exhaust is a "may". Declining leaves the enemy SOR_046 ready.

## GIVEN
CommonSetup: yyk/rrk/{myResources:3}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_189

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:READY

---

# WhenPlayed_ExhaustUnit
#// SEC_189 Lurking Snub Fighter (Space, 2/3, cost 3) — When Played: you may exhaust a unit. P1 plays it
#//   and exhausts the enemy SOR_046.

## GIVEN
CommonSetup: yyk/rrk/{myResources:3}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_189

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# PlayViaPlot_ExhaustUnit
#// SEC_189 Lurking Snub Fighter — the When Played "may exhaust a unit" still fires when Lurking Snub
#//   Fighter is played from resources via PLOT on a leader deploy. It sits in P1's resources; deploying
#//   the leader offers to play it via Plot, and its When Played then exhausts the enemy SOR_046.

## GIVEN
CommonSetup: yyk/grw
P1OnlyActions: true
WithP1Resources: 1:SEC_189:1,7:SOR_095:1
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
