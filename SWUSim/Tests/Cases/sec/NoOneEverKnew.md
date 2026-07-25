# ExhaustPerOfficial
#// SEC_196 No One Ever Knew (event, cost 2) — For each friendly Official unit, exhaust an enemy unit.
#//   With one Official (SEC_041) in play, P1 exhausts one enemy (SOR_046).

## GIVEN
CommonSetup: yyk/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SEC_041:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_196

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# TwoOfficials_ExhaustTwo
#// SEC_196 No One Ever Knew — with TWO friendly Official units (SEC_041 + TWI_032) and three enemy units,
#//   P1 exhausts two of them. The third enemy (SOR_128) stays ready, proving the count is bounded by the
#//   number of friendly Officials.

## GIVEN
CommonSetup: yyk/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SEC_041:1:0
WithP1GroundArena: TWI_032:1:0
WithP2GroundArena: SOR_164:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:1:0
WithP1Hand: SEC_196

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:EXHAUSTED
P2GROUNDARENAUNIT:2:READY

---

# MoreOfficialsThanEnemies_ExhaustAllAvailable
#// SEC_196 — with TWO friendly Officials but only ONE enemy unit, the effect caps at what is available:
#//   only the single enemy (SOR_164) is exhausted (no second target to exhaust).

## GIVEN
CommonSetup: yyk/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SEC_041:1:0
WithP1GroundArena: TWI_032:1:0
WithP2GroundArena: SOR_164:1:0
WithP1Hand: SEC_196

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
