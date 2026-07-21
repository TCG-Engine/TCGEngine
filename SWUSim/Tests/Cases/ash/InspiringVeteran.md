# AdvantageToExhaustedUnits
#// ASH_205 Inspiring Veteran (Ground, 3/3, cost 3) — When Played: give an Advantage token to each of up
#// to 3 exhausted units. P1 controls SOR_095 (exhausted, g0), SOR_046 (READY, g1) and SOR_237 (exhausted,
#// s0). ASH_205 enters exhausted at g2. The offered set is the exhausted units (SOR_095, SOR_237, ASH_205)
#// — the READY SOR_046 is excluded. Choosing all 3 gives each 1 Advantage token; SOR_046 gets none.
## GIVEN
CommonSetup: yyw/yyk/{myResources:3;handCardIds:ASH_205}
WithP1GroundArena: SOR_095:0:0
WithP1GroundArena: SOR_046:1:0
WithP1SpaceArena: SOR_237:0:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&mySpaceArena-0&myGroundArena-2
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:1
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:ADVANTAGECOUNT:0
P1GROUNDARENAUNIT:2:CARDID:ASH_205
P1GROUNDARENAUNIT:2:ADVANTAGECOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:1

---

# GiveToSubsetOnly
#// ASH_205 Inspiring Veteran — "up to 3" may be fewer. P1 gives an Advantage token to only SOR_095; the
#// other exhausted units (ASH_205 itself, SOR_237) get none.
## GIVEN
CommonSetup: yyw/yyk/{myResources:3;handCardIds:ASH_205}
WithP1GroundArena: SOR_095:0:0
WithP1SpaceArena: SOR_237:0:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:1
P1GROUNDARENAUNIT:1:ADVANTAGECOUNT:0
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:0
