# AllThreeClausesResolve
#// IC27_168 Cunning Ploy — 4 cost, Cunning+Cunning, Event, Trait: Trick (non-unique).
#// Text: "Look at an opponent's hand. You may discard a card from it. If you do, that player draws a card.
#//        Exhaust an enemy unit.
#//        You may attack with a unit. It gets +3/+0 for this attack."
#// THREE INDEPENDENT clauses (the LOF_223 Force Illusion family): each must resolve on its own, and a
#// clause that fizzles must not take the others down with it.
#// Cunning+Cunning is DOUBLE-PIP — the yyw seat (Cunning base + Cunning leader) covers both, keeping
#// him at the printed 4.
#// Board is arranged so every choice is deterministic: the only enemy unit is in SPACE (so the exhaust
#// auto-resolves) and P1 attacks on the GROUND where only the base is a legal target.
#// Opponent hand 2 -> discard 1 -> draw 1 = 2. Attacker 3 power +3 = 6 to the base.

## GIVEN
CommonSetup: yyw/yyw/{myResources:4;myhandCardIds:IC27_168}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_225:1:0
WithP2Hand: [SOR_046 SOR_237]
WithP2Deck: [SEC_080 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirHand-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2HANDCOUNT:2
P2DISCARDCOUNT:1
P2DECKCOUNT:1
P2SPACEARENAUNIT:0:CARDID:SOR_225
P2SPACEARENAUNIT:0:EXHAUSTED
P2BASEDMG:6
P1GROUNDARENAUNIT:0:POWER:3

---

# DeclineTheDiscard_NoDrawButOtherClausesResolve
#// "If you do, that player draws" — the draw is gated on the discard actually happening. Declining
#// leaves the opponent's hand and deck untouched, while clauses 2 and 3 still resolve in full.

## GIVEN
CommonSetup: yyw/yyw/{myResources:4;myhandCardIds:IC27_168}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_225:1:0
WithP2Hand: [SOR_046 SOR_237]
WithP2Deck: [SEC_080 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2HANDCOUNT:2
P2DISCARDCOUNT:0
P2DECKCOUNT:2
P2SPACEARENAUNIT:0:EXHAUSTED
P2BASEDMG:6

---

# EmptyOpponentHand_FirstClauseFizzles_RestStillResolve
#// INDEPENDENCE #1: nothing to look at or discard, and no pointless prompt — but the exhaust and the
#// attack still happen.

## GIVEN
CommonSetup: yyw/yyw/{myResources:4;myhandCardIds:IC27_168}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_225:1:0
WithP2Deck: [SEC_080 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2HANDCOUNT:0
P2DECKCOUNT:2
P2SPACEARENAUNIT:0:EXHAUSTED
P2BASEDMG:6

---

# NoEnemyUnit_SecondClauseFizzles_RestStillResolve
#// INDEPENDENCE #2: no enemy unit to exhaust, yet the discard and the attack both resolve.

## GIVEN
CommonSetup: yyw/yyw/{myResources:4;myhandCardIds:IC27_168}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2Hand: [SOR_046 SOR_237]
WithP2Deck: [SEC_080 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirHand-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2HANDCOUNT:2
P2DISCARDCOUNT:1
P2BASEDMG:6

---

# NoReadyFriendlyUnit_ThirdClauseFizzles_RestStillResolve
#// INDEPENDENCE #3: the only friendly unit is already exhausted, so there is nobody to attack with —
#// but the discard and the exhaust still land.

## GIVEN
CommonSetup: yyw/yyw/{myResources:4;myhandCardIds:IC27_168}
P1OnlyActions: true
WithP1GroundArena: SOR_095:0:0
WithP2SpaceArena: SOR_225:1:0
WithP2Hand: [SOR_046 SOR_237]
WithP2Deck: [SEC_080 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirHand-0

## EXPECT
P2HANDCOUNT:2
P2DISCARDCOUNT:1
P2SPACEARENAUNIT:0:EXHAUSTED
P2BASEDMG:0

---

# DeclineTheAttack_FirstTwoStillResolve
#// TAKE/DECLINE on the third clause: "You may attack" is optional, and passing leaves the attacker
#// ready and the base untouched while the first two clauses stay applied.

## GIVEN
CommonSetup: yyw/yyw/{myResources:4;myhandCardIds:IC27_168}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_225:1:0
WithP2Hand: [SOR_046 SOR_237]
WithP2Deck: [SEC_080 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirHand-0
- P1>AnswerDecision:-

## EXPECT
P2HANDCOUNT:2
P2DISCARDCOUNT:1
P2SPACEARENAUNIT:0:EXHAUSTED
P2BASEDMG:0
P1GROUNDARENAUNIT:0:READY

---

# ExhaustTargetsOnlyEnemyUnits
#// SCOPE on clause 2: "an ENEMY unit" — a friendly unit must never be exhausted by it. Both a friendly
#// and an enemy unit are on the board and the enemy is the only legal pick, so it auto-resolves onto
#// the enemy and P1's second unit stays ready.

## GIVEN
CommonSetup: yyw/yyw/{myResources:4;myhandCardIds:IC27_168}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_225:1:0
WithP2Deck: [SEC_080 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2SPACEARENAUNIT:0:EXHAUSTED
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:READY
P1GROUNDARENAUNIT:0:READY

---

# TwinSuns_LooksAtTheCHOSENSeatsHand
#// ⚠ THE SEAT-COUNT CELL — added 2026-08-24. Clause 1: "Look at AN OPPONENT's hand. You may discard a
#// card from it. If you do, that player draws a card."
#// ⚠⚠ PREVIEW-SET ASSUMPTION, FLAGGED: IC27 is NOT in card-specific-rulings.md (released sets only). The
#// reading comes from the exact released analogue SHD_184 Bazine Netal, which prints the clause word for
#// word and carries the "controlling player chooses" ruling. Re-check when IC27 releases.
#// ⚠ FILTER to opponents holding a card. On zero eligible the card must still fall through to CLAUSE 2
#//   (the exhaust), not fizzle entirely — that path is preserved by the early Ic27168ExhaustEnemy call.
#// SEAT 3 is picked; P1 discards from seat 3 and SEAT 3 draws. Seat 2 untouched.
#// Mutation check: drop the $opp argument to SWULookAtOpponentHand and this reds.

## GIVEN
CommonSetup: yyw/yyw/{myResources:4;myhandCardIds:IC27_168}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_225:1:0
WithP2Hand: [SOR_046 SOR_237]
WithP3Hand: [SOR_046 SOR_237]
WithP3Deck: [SEC_080 SEC_080]
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:P3
- P1>AnswerDecision:p3Hand-0

## EXPECT
SEATCOUNT:4
P3HANDCOUNT:2
P3DISCARDCOUNT:1
P2HANDCOUNT:2
P2DISCARDCOUNT:0
