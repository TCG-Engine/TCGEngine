# OnDraw_AdvantageToSelf
#// ASH_169 Axe Woves (Ground, 2/4) — When you draw 1+ cards: give an Advantage token to this unit. P1
#// plays Patrolling V-Wing (SOR_111, "When Played: draw a card"); the draw gives Axe Woves an Advantage.
## GIVEN
CommonSetup: ggw/rrk/{myResources:4;handCardIds:SOR_111}
WithP1Deck: SOR_095
WithP1GroundArena: ASH_169:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_169
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:1

---

# NoDraw_NoAdvantage
#// ASH_169 Axe Woves — the trigger requires drawing a card. Playing SOR_095 (no draw) gives Axe Woves no
#// Advantage token.
## GIVEN
CommonSetup: ggw/rrk/{myResources:4;handCardIds:SOR_095}
WithP1GroundArena: ASH_169:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
