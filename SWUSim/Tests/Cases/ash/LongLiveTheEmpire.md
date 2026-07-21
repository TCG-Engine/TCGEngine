# DefeatImperialResourceTop
#// ASH_103 Long Live the Empire (Event, cost 2) — Defeat a friendly Imperial unit. If you do, resource the
#// top card of your deck. P1 defeats SEC_080 and adds the top deck card to its resources (2 → 3).
## GIVEN
CommonSetup: ggk/ggk/{myResources:2;handCardIds:ASH_103}
WithP1GroundArena: SEC_080:1:0
WithP1Deck: SOR_095
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:0
P1RESCOUNT:3

---

# NoImperialUnit_NoResource
#// ASH_103 Long Live the Empire — the resource is gated on defeating a friendly Imperial. With only the
#// non-Imperial SOR_095 in play, nothing is defeated and no card is resourced.
## GIVEN
CommonSetup: ggk/ggk/{myResources:2;handCardIds:ASH_103}
WithP1GroundArena: SOR_095:1:0
WithP1Deck: SOR_095
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:1
P1RESCOUNT:2
P1DECKCOUNT:1
