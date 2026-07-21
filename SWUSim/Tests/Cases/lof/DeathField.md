# DamageDraw
#// LOF_141 Death Field — Deal 2 damage to each non-Vehicle enemy unit. If you control a Force unit, draw a
#// card. Both enemy units take 2; P1 controls Plo Koon (Force) so draws SOR_059 into hand.

## GIVEN
CommonSetup: rrk/ggw/{myResources:4;handCardIds:LOF_141}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP1Deck: SOR_059
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_059:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:1:DAMAGE:2
P1HANDCOUNT:1

---

# VehicleExcluded_NoForceNoDraw
#// LOF_141 Death Field — only NON-Vehicle enemy units take the 2 damage, and the draw is gated on controlling
#// a Force unit. P1's only unit is Battlefield Marine (SOR_095, non-Force), so no card is drawn. The enemy
#// Consular Security Force (SOR_046, non-Vehicle) takes 2 while the AT-ST (SOR_232, Vehicle) takes 0: it
#// deals 2 damage and does not draw a card because no Force unit is controlled.

## GIVEN
CommonSetup: rrk/ggw/{myResources:4;handCardIds:LOF_141}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Deck: SOR_059
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_232:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:1:DAMAGE:0
P1HANDCOUNT:0
P1DECKCOUNT:1

---

# DrawWithNoDamageableEnemies
#// LOF_141 Death Field — with a Force unit controlled (Plo Koon, LOF_050) the draw happens even when there is
#// nothing to damage: the enemy only has an AT-ST (SOR_232, Vehicle) which is immune, so 0 damage is dealt but
#// a card is still drawn. Ref: "can be used to draw a card if the opponent has no non-Vehicle units".

## GIVEN
CommonSetup: rrk/ggw/{myResources:4;handCardIds:LOF_141}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP1Deck: SOR_059
WithP2GroundArena: SOR_232:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1HANDCOUNT:1
P1DECKCOUNT:0
