# EachSpareDeal3
#// LOF_177 — Each player chooses a unit they control; deal 3 damage to each unit not chosen. P1 has one unit
#// (auto-spared); P2 spares SOR_046, so only SOR_059 takes 3.

## GIVEN
CommonSetup: rrk/ggw/{myResources:4;handCardIds:LOF_177}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: SOR_059:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:1:DAMAGE:3

---

# EachPlayerSparesOne_RestTake3
#// LOF_177 Time of Crisis — each player chooses a unit they control (spared); every OTHER unit takes 3.
#// P1 and P2 each have two SOR_046 (3/7). P1 spares its index-0, P2 spares its OWN index-0 (answered on
#// P2's queue). The two un-spared units each take 3 damage; the spared ones take none.
## GIVEN
CommonSetup: rrk/ggw/{myResources:5;handCardIds:LOF_177}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:1:DAMAGE:3

---

# P1NoUnits_OnlyP2Picks
#// LOF_177 — if the PLAYER controls no units, only the opponent chooses a unit to spare; every other
#// enemy unit takes 3. P1 has no units; P2 has two SOR_046 (3/7). P2 spares idx-0; idx-1 takes 3.
## GIVEN
CommonSetup: rrk/ggw/{myResources:5;handCardIds:LOF_177}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:1:DAMAGE:3

---

# P2NoUnits_OnlyP1Picks
#// LOF_177 — if the OPPONENT controls no units, only the player chooses a unit to spare; every other
#// friendly unit takes 3. P1 has two SOR_046; P2 has none. P1 spares idx-0; idx-1 takes 3.
## GIVEN
CommonSetup: rrk/ggw/{myResources:5;handCardIds:LOF_177}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:DAMAGE:3

---

# P2AutoSingle_P1Picks
#// LOF_177 — opponent's lone unit is auto-spared (single legal choice → no prompt). P1 has two SOR_046
#// and spares idx-0; P1 idx-1 takes 3. P2's single SOR_095 is auto-spared and takes 0.
## GIVEN
CommonSetup: rrk/ggw/{myResources:5;handCardIds:LOF_177}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# EmptyBoard_NoEffect
#// LOF_177 — with no units on either side the event has no effect; it still plays and goes to discard.
## GIVEN
CommonSetup: rrk/ggw/{myResources:5;handCardIds:LOF_177}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1HANDCOUNT:0

---

# CrossArena_SpareOnePerPlayer
#// LOF_177 hits BOTH arenas. Each player spares one unit anywhere; every other unit (ground OR space)
#// takes 3. P1 spares its ground SOR_046 (its space Avenger still takes 3); P2 spares its space Avenger
#// (its ground SOR_046 takes 3).
## GIVEN
CommonSetup: rrk/ggw/{myResources:5;handCardIds:LOF_177}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1SpaceArena: SOR_040:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_040:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>AnswerDecision:mySpaceArena-0
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1SPACEARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:3
P2SPACEARENAUNIT:0:DAMAGE:0
