# DiscardToDeckBuff
#// LOF_104 Luminous Beings — Put up to 3 Force units from your discard on the bottom of your deck (random
#// order). Give that many units +4/+4 for this phase. P1 has one Force unit (LOF_050) in discard and one
#// unit (SOR_046, 3/7) in play; moving the Force unit grants SOR_046 +4/+4 → 7/11.

## GIVEN
CommonSetup: ggw/rrk/{myResources:6;handCardIds:LOF_104;discardCardIds:LOF_050}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:0:HP:11
#// Discard holds only the LOF_104 event now; the Force unit (LOF_050) was moved to the deck bottom.
P1DISCARDCOUNT:1

---

# TwoUnits_BuffTwo
#// LOF_104 Luminous Beings — moving 2 Force units from discard (SOR_051 Luke, SOR_045 Yoda) lets P1 give
#// +4/+4 to exactly 2 units. SOR_046 (3/7 → 7/11) and SOR_095 (3/3 → 7/7) both buffed. The 2 Force units go
#// to the bottom of the deck, leaving only the LOF_104 event in discard. Intended: "put up to 3 Force units ...
#// give that many units +4/+4".

## GIVEN
CommonSetup: ggw/rrk/{myResources:6;handCardIds:LOF_104;discardCardIds:SOR_051,SOR_045}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0&myDiscard-1
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:0:HP:11
P1GROUNDARENAUNIT:1:POWER:7
P1GROUNDARENAUNIT:1:HP:7
P1DISCARDCOUNT:1

---

# ChooseNothing_NoBuff
#// LOF_104 Luminous Beings — the move is "up to 3", so P1 may choose nothing. With no Force units moved, no
#// unit gets +4/+4 and both Force units stay in the discard pile (alongside the played event). SOR_046
#// remains a printed 3/7. Intended: "give that many units +4/+4 for the phase (choose 0)".

## GIVEN
CommonSetup: ggw/rrk/{myResources:6;handCardIds:LOF_104;discardCardIds:SOR_051,SOR_045}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:7
P1DISCARDCOUNT:3

---

# BuffExpiresNextPhase
#// LOF_104 Luminous Beings — the +4/+4 lasts only "for this phase". P1 moves 1 Force unit (SOR_051) and
#// buffs SOR_046 to 7/11, then both players pass to end the action phase; after regroup the bonus expires
#// and SOR_046 returns to a printed 3/7.

## GIVEN
CommonSetup: ggw/rrk/{myResources:6;handCardIds:LOF_104;discardCardIds:SOR_051}
WithActivePlayer: 1
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0
- P1>AnswerDecision:myGroundArena-0
- P2>Pass
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:7
