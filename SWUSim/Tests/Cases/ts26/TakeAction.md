# CostReducedByLeaderUnit
#// TS26_071 Take Action (Event, cost 3) — costs 1 resource less per friendly leader unit. With one
#// deployed leader unit in play, the event costs 2 (3 resources → 1 left after paying). Deal 3 to the
#// enemy unit (chosen over the friendly leader unit).
## GIVEN
CommonSetup: rrk/rrk/{myResources:3;myLeader:SOR_005:1:1}
WithP1Hand: TS26_071
WithP2GroundArena: LAW_124:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1RESAVAILABLE:1
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# Deal3
#// TS26_071 Take Action (Event, cost 3, Aggression) — Deal 3 damage to a unit. (No friendly leader units,
#// so no cost reduction here.) The single enemy unit is the only target → auto-resolves.
## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
WithP1Hand: TS26_071
WithP2GroundArena: LAW_124:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
