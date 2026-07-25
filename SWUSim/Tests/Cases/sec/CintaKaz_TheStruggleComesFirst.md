# WhenPlayed_MayAttack
#// SEC_172 Cinta Kaz (Ground, 5/5, cost 6) — When Played: you may attack with a unit. P1 plays SEC_172
#//   and attacks with the ready SEC_041 → P2's base takes 1.

## GIVEN
CommonSetup: rrk/rrk/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SEC_041:1:0
WithP1Hand: SEC_172

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:1

---

# PlayedViaPlot_MayAttack
#// SEC_172 Cinta Kaz has Plot — "When you deploy a leader, you may play this card from your resources."
#// Deploying SOR_013 Cassian Andor opens the Plot window; playing Cinta Kaz from resources still fires
#// her When Played "you may attack with a unit" — P1 attacks with the ready SOR_095 (3 power) into P2's
#// base for 3.

## GIVEN
CommonSetup: rrw/rrk/{myLeader:SOR_013}
P1OnlyActions: true
WithP1Resources: 1:SEC_172:1,13:SOR_095:1
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1LEADER:DEPLOYED
P2BASEDMG:3
