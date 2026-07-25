# AdvantageToTwoUnits
#// ASH_264 A New Order (Event, cost 1) — Give an Advantage token to each of up to 2 units. P1 picks both
#// of its units (SOR_095 ground, SOR_237 space); each gains 1 Advantage token.
## GIVEN
CommonSetup: yyw/yyk/{myResources:1;handCardIds:ASH_264}
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&mySpaceArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:1

---

# SingleUnit
#// ASH_264 A New Order — "up to 2" may be just one. P1 gives an Advantage token to only SOR_095.
## GIVEN
CommonSetup: yyw/yyk/{myResources:1;handCardIds:ASH_264}
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:1
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:0

---

# AdvantageToTwoEnemyUnits
#// ASH_264 A New Order — "each of up to 2 units" is not restricted to friendly units. P1 picks both enemy
#// units (SOR_239 ground, SHD_187 space); each gains an Advantage token, its own units get none.
## GIVEN
CommonSetup: yyw/yyk/{myResources:1;handCardIds:ASH_264}
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_239:1:0
WithP2SpaceArena: SHD_187:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirSpaceArena-0
## EXPECT
P2GROUNDARENAUNIT:0:ADVANTAGECOUNT:1
P2SPACEARENAUNIT:0:ADVANTAGECOUNT:1
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:0

---

# AdvantageToOneEnemyUnit
#// ASH_264 A New Order — a single enemy unit may be chosen. P1 gives an Advantage token to only the enemy
#// SOR_239; all other units get none.
## GIVEN
CommonSetup: yyw/yyk/{myResources:1;handCardIds:ASH_264}
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_239:1:0
WithP2SpaceArena: SHD_187:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:ADVANTAGECOUNT:1
P2SPACEARENAUNIT:0:ADVANTAGECOUNT:0
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:0

---

# AdvantageToOneEnemyAndOneFriendly
#// ASH_264 A New Order — the two chosen units may be one enemy and one friendly. P1 gives an Advantage
#// token to the enemy SOR_239 and to the friendly SOR_095; the space units get none.
## GIVEN
CommonSetup: yyw/yyk/{myResources:1;handCardIds:ASH_264}
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_239:1:0
WithP2SpaceArena: SHD_187:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&myGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:ADVANTAGECOUNT:1
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:1
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:0
P2SPACEARENAUNIT:0:ADVANTAGECOUNT:0

---

# ChooseNothing
#// ASH_264 A New Order — "up to 2" allows choosing zero units; the event resolves with no Advantage tokens
#// handed out.
## GIVEN
CommonSetup: yyw/yyk/{myResources:1;handCardIds:ASH_264}
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_239:1:0
WithP2SpaceArena: SHD_187:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:0
P2GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
P2SPACEARENAUNIT:0:ADVANTAGECOUNT:0
P1DISCARDCOUNT:1
