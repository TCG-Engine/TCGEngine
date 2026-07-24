# WhenPlayed_Deal2Ready
#// SEC_152 Strike Force X-Wing (Unit, Aggression/Heroism, cost 4) — When Played: may deal 2 to a READY
#//   unit. (Plot dormant from hand.) Hits the ready enemy SOR_046.

## GIVEN
CommonSetup: rrw/rrk/{myResources:4}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_152

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1NODECISION

---

# PlayedViaPlot_Deal2Ready
#// SEC_152 Strike Force X-Wing has Plot — "When you deploy a leader, you may play this card from your
#//   resources." Deploying SOR_013 Cassian Andor opens the Plot window; playing the X-Wing from resources
#//   still fires its When Played "deal 2 to a ready unit" against the ready enemy SOR_046.

## GIVEN
CommonSetup: rrw/rrk/{myLeader:SOR_013}
P1OnlyActions: true
WithP1Resources: 1:SEC_152:1,13:SOR_095:1
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1LEADER:DEPLOYED
P2GROUNDARENAUNIT:0:DAMAGE:2
P1NODECISION
