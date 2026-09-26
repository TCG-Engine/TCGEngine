# PeekDefeated_Draw1
#// TWI_188 Wartime Profiteering (Event, cost 1, Cunning/Villainy, Supply) — "Look at cards from the top of
#// your deck equal to the number of units that were defeated this phase. Draw 1 and put the others on the
#// bottom." A 3/1 vs 3/1 trade defeats 2 units this phase, so 2 cards are looked at; drawing 1 (SOR_046).

## GIVEN
CommonSetup: yyk/rrk/{myResources:1;handCardIds:TWI_188}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_128:1:0
WithP1Deck: [SOR_046 SOR_046 SOR_046]

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>AnswerDecision:SOR_046

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:2

---

# ThreeSeat_DefeatsAtAFarSeat_AreCounted
#// TWI_188 — "equal to the number of UNITS that were defeated this phase". The word is UNQUALIFIED: not
#// "friendly", not "enemy", not "you defeated". Every unit that died this phase counts, on any seat.
#//
#// ⚠ THE BUG: the count was `GlobalEffectCount(1, …) + GlobalEffectCount(2, …)` — literal seats 1 and 2.
#// SWU_FRIENDLY_DEFEATED is stamped on the defeated unit's CONTROLLER, so at 3+ seats every defeat on
#// seat 3 or 4 was missing from the total. Two seats cannot observe this: "seat 1 + seat 2" is the whole
#// table there, which is exactly why the section above passes either way.
#//
#// Here P2's 8/8 kills two of P3's 3/1s across two turns. P1 was not involved in either. Two units were
#// defeated this phase, so P1 looks at TWO cards and may draw the SECOND one from the top (SOR_128) —
#// unreachable at a count of 1, and at the broken count of ZERO the event fizzles with no decision at all.

## GIVEN
CommonSetup3P: yyk/rrk/bbk
SkipPreGame: true
WithActivePlayer: 2
WithP1Resources: 2
WithP1Hand: TWI_188
WithP1Deck: [SOR_046 SOR_128 SOR_039]
WithP2GroundArena: SOR_039:1:0
WithP2GroundArena: SOR_039:1:0
WithP3GroundArena: SOR_128:1:0
WithP3GroundArena: SOR_128:1:0

## WHEN
- P2>AttackGroundArena:0:P3G0
- P3>Pass
- P1>Pass
- P2>AttackGroundArena:1:P3G1
- P3>Pass
- P1>PlayHand:0
- P1>AnswerDecision:SOR_128

## EXPECT
P3GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1HANDCARD:0:SOR_128
P1DECKCOUNT:2
