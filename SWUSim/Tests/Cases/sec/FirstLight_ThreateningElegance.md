# AttacksAndDefeats_MayDraw
#// SEC_088 First Light (Space, 5/7) — Ambush + "When this unit attacks and defeats a unit: you may draw
#//   a card." SEC_088 attacks and kills SOR_237 (2/3); P1 chooses to draw.

## GIVEN
CommonSetup: ggk/rrk
WithActivePlayer: 1
WithP1SpaceArena: SEC_088:1:0
WithP2SpaceArena: SOR_237:1:0
WithP1Deck: SOR_095

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2SPACEARENACOUNT:0
P1HANDCOUNT:1
P1DECKCOUNT:0

---

# AttacksButDoesNotDefeat_NoDraw
#// SEC_088 First Light — the draw only triggers on attack-AND-defeat. SEC_088 (5/7) attacks JTL_069
#//   Munificent Frigate (4/7): it deals 5, the frigate survives (7 HP), so no unit is defeated and no
#//   draw prompt appears.

## GIVEN
CommonSetup: ggk/rrk
WithActivePlayer: 1
WithP1SpaceArena: SEC_088:1:0
WithP2SpaceArena: JTL_069:1:0
WithP1Deck: SOR_095

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P2SPACEARENACOUNT:1
P1HANDCOUNT:0
P1NODECISION

---

# AttacksAndDefeats_DeclineDraw
#// SEC_088 First Light — the draw is optional ("you may"). SEC_088 attacks and kills SOR_237, then
#//   P1 declines the draw → hand stays empty.

## GIVEN
CommonSetup: ggk/rrk
WithActivePlayer: 1
WithP1SpaceArena: SEC_088:1:0
WithP2SpaceArena: SOR_237:1:0
WithP1Deck: SOR_095

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:NO

## EXPECT
P2SPACEARENACOUNT:0
P1HANDCOUNT:0

---

# PlayedViaPlot_AmbushAttackStillFeedsTheDrawTrigger
#// SEC_088 First Light — Plot + Ambush together: deploying a leader lets P1 play First Light from the
#// resource row, Ambush immediately attacks P2's SOR_237 (2/3) and kills it, and the "attacks and
#// defeats a unit" draw still fires off that Ambush attack. Proves the Plot entry path reaches the
#// Ambush attack AND the attack-defeat trigger, not just the unit landing in the arena.

## GIVEN
CommonSetup: ggk/rrk
P1OnlyActions: true
WithP1Resources: 1:SEC_088:1,9:SEC_080:1
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES

## EXPECT
P1LEADER:DEPLOYED
P2SPACEARENACOUNT:0
P1HANDCOUNT:1
P1DECKCOUNT:1
