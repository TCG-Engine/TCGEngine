# Reprint041
#// IBH_041 C-3PO (reprint of IBH_019) — When Played: if Cunning unit, draw. Confirms the duplicate.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: IBH_041
WithP1GroundArena: SOR_207:1:0
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:0
P1NODECISION

---

# WhenPlayed_CunningControlled_Draws
#// IBH_019 C-3PO (Ground, 1/4, Command/Heroism, cost 3) — When Played: if you control a Cunning unit,
#//   draw a card. P1 controls SOR_207 (Cunning). Playing C-3PO draws 1.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: IBH_019
WithP1GroundArena: SOR_207:1:0
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1HANDCOUNT:1
P1DECKCOUNT:0
P1NODECISION

---

# WhenPlayed_NoCunning_NoDraw
#// IBH_019 C-3PO — When Played with NO Cunning unit controlled (C-3PO is Command/Heroism): no draw.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: IBH_019
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:1
P1NODECISION
