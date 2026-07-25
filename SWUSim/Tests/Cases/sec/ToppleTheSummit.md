# Deal3EachDamaged
#// SEC_183 Topple the Summit (Event, Aggression, cost 5) — "Deal 3 to each damaged unit." (Plot auto.)
#//   The two damaged units take 3 more (→ 5); the undamaged unit is untouched.

## GIVEN
CommonSetup: rrk/grw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:2
WithP2GroundArena: SOR_046:1:2
WithP2GroundArena: SOR_095:1:0
WithP1Hand: SEC_183

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:5
P2GROUNDARENAUNIT:0:DAMAGE:5
P2GROUNDARENAUNIT:1:DAMAGE:0
P1NODECISION

---

# PlayedViaPlot_Deal3EachDamaged
#// SEC_183 Topple the Summit has Plot — "When you deploy a leader, you may play this card from your
#//   resources, paying its cost." Deploying SHD_008 Boba Fett opens the Plot window; playing Topple from
#//   resources deals 3 to each damaged unit (both 2-damage units → 5) and leaves the undamaged one alone.

## GIVEN
CommonSetup: rrk/grw/{myLeader:SHD_008}
P1OnlyActions: true
WithP1Resources: 1:SEC_183:1,13:SOR_095:1
WithP1GroundArena: SOR_046:1:2
WithP2GroundArena: SOR_046:1:2
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myResources-0

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENAUNIT:0:DAMAGE:5
P2GROUNDARENAUNIT:0:DAMAGE:5
P2GROUNDARENAUNIT:1:DAMAGE:0
P1NODECISION
