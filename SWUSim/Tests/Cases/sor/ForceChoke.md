# ForceUnit_CostReducedAndDamage
#// SOR_139 (Aggression/Villainy event, cost 2, Force) — "If you control a FORCE unit, this event costs
#// 1 less. Deal 5 damage to a non-Vehicle unit. That unit's controller draws a card." P1 controls a
#// Force unit (SOR_004) → SOR_139 costs 1 (2 ready resources → 1 left after). It deals 5 to the enemy
#// SOR_046 (3/7 → survives at DAMAGE:5) and that unit's controller (P2) draws a card from their deck.
#// Two non-Vehicle units are in play (friendly SOR_004 + enemy SOR_046) so the target is chosen.

## GIVEN
CommonSetup: rrk/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_004:1:0
WithP2GroundArena: SOR_046:1:0
WithP2Deck: SOR_237
WithP1Hand: SOR_139

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1RESAVAILABLE:1
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:5
P2HANDCOUNT:1
P2DECKCOUNT:0

---

# NoForceUnit_FullCost
#// SOR_139 — cost-reduction guard: P1 controls only a non-Force unit (SOR_128 Imperial), so the "if you
#// control a Force unit" discount does NOT apply → full cost 2 (2 ready resources → 0 left). The damage
#// + draw effect still resolves: 5 to the enemy SOR_046, P2 draws.

## GIVEN
CommonSetup: rrk/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0
WithP2Deck: SOR_237
WithP1Hand: SOR_139

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1RESAVAILABLE:0
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:5
P2HANDCOUNT:1
P2DECKCOUNT:0
