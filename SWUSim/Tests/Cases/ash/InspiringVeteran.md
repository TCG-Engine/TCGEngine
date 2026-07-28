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

---

# ChooseTwoOfExhausted
#// ASH_205 Inspiring Veteran — "up to 3" may be exactly 2. P1 controls SOR_095 (exhausted, g0), SOR_046
#// (READY, g1) and SOR_237 (exhausted, s0); ASH_205 enters exhausted at g2. Choosing SOR_095 + ASH_205
#// gives each 1 Advantage; SOR_237 (unpicked) and the READY SOR_046 get none.
## GIVEN
CommonSetup: yyw/yyk/{myResources:3;handCardIds:ASH_205}
WithP1GroundArena: SOR_095:0:0
WithP1GroundArena: SOR_046:1:0
WithP1SpaceArena: SOR_237:0:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-2
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:1
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:ADVANTAGECOUNT:0
P1GROUNDARENAUNIT:2:CARDID:ASH_205
P1GROUNDARENAUNIT:2:ADVANTAGECOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:0

---

# ChooseNothingDeclines
#// ASH_205 Inspiring Veteran — "up to 3" allows choosing nothing. Declining the token gift leaves every
#// exhausted unit (SOR_095, SOR_237, ASH_205 itself) with no Advantage token.
## GIVEN
CommonSetup: yyw/yyk/{myResources:3;handCardIds:ASH_205}
WithP1GroundArena: SOR_095:0:0
WithP1SpaceArena: SOR_237:0:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
P1GROUNDARENAUNIT:1:ADVANTAGECOUNT:0
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:0

---

# EnemyExhaustedIsSelectable
#// ASH_205 Inspiring Veteran — the offered set is EVERY exhausted unit, friendly or enemy. P1 controls
#// exhausted SOR_095 (g0); the enemy's SOR_237 (s0) is exhausted too. P1 may hand an Advantage token to
#// the enemy unit; here it gives one to the enemy SOR_237 and none to its own SOR_095 or ASH_205.
## GIVEN
CommonSetup: yyw/yyk/{myResources:3;handCardIds:ASH_205}
WithP1GroundArena: SOR_095:0:0
WithP2SpaceArena: SOR_237:0:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
## EXPECT
P2SPACEARENAUNIT:0:CARDID:SOR_237
P2SPACEARENAUNIT:0:ADVANTAGECOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
P1GROUNDARENAUNIT:1:CARDID:ASH_205
P1GROUNDARENAUNIT:1:ADVANTAGECOUNT:0
