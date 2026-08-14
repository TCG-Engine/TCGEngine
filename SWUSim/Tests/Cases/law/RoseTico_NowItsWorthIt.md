# EntersExhausted_UniqueOnly
#// LAW_223 Rose Tico — guard: controlling only a UNIQUE unit (SOR_181 Jabba the Hutt) does NOT satisfy
#// "a non-unique unit", so Rose enters EXHAUSTED (proves the rule is non-unique, not any-unit).
#// COVERAGE: offer=N/A (static enter-play conditional — no targets, no offers) · reqboundary=
#//           RescuedEntersReady (the condition is evaluated at an enter-play instant reached across a
#//           P1>Pass + a P2 play + a P1 play, several requests after setup) · control=
#//           FreePlayFromOppDeck_NoNonUnique_EntersExhausted + FreePlayFromOppDeck_WithNonUnique_EntersReady
#//           (Rose owned by P2, entering under P1's control — the condition must read the CONTROLLER's
#//           units, and the owner's non-unique unit must NOT count) · boundary pair=
#//           EntersReady_WithNonUnique vs EntersExhausted_UniqueOnly / EntersExhausted_NoUnits (same
#//           play, condition true/false) · decline=N/A (no "you may" — entering ready is automatic when
#//           the condition holds)

## GIVEN
CommonSetup: yyk/rrk/{myResources:10}
P1OnlyActions: true
WithP1GroundArena: SOR_181:1:0
WithP1Hand: LAW_223

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:LAW_223
P1GROUNDARENAUNIT:1:EXHAUSTED

---

# EntersReady_WithNonUnique
#// LAW_223 Rose Tico (5/5 ground, Resistance) — "If you control a non-unique unit, this unit enters play
#// ready." P1 controls SEC_080 (non-unique) → Rose (played at index 1) enters READY.

## GIVEN
CommonSetup: yyk/rrk/{myResources:10}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: LAW_223

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:LAW_223
P1GROUNDARENAUNIT:1:READY

---

# EntersExhausted_NoUnits
#// LAW_223 Rose Tico — controlling NO units at all does not satisfy "a non-unique unit", so Rose enters
#// EXHAUSTED. Played into an empty board (P2 has a unit, but that is not friendly).

## GIVEN
CommonSetup: yyk/rrk/{myResources:10}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
WithP1Hand: LAW_223

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_223
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# EntersReady_NonUniqueSpaceUnit
#// LAW_223 Rose Tico — the non-unique unit can be in EITHER arena. Controlling only SOR_178 Cartel Spacer
#// (a non-unique SPACE unit) still lets Rose (a ground unit) enter play READY.

## GIVEN
CommonSetup: yyk/rrk/{myResources:10}
P1OnlyActions: true
WithP1SpaceArena: SOR_178:1:0
WithP1Hand: LAW_223

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_223
P1GROUNDARENAUNIT:0:READY

---

# RescuedEntersReady
#// LAW_223 Rose Tico — being RESCUED from capture is entering play, so the conditional applies then too.
#// P2 plays SHD_120 Discerning Veteran, capturing Rose (chosen over the marine). P1 answers with SHD_079
#// Rival's Fall on the Veteran: Rose is rescued back to P1's ground arena, and because P1 controls the
#// non-unique Battlefield Marine she enters play READY.

## GIVEN
CommonSetup: bbw/ggk/{
  myResources:6;
  theirResources:5
}
WithP1GroundArena: [LAW_223:1:0 SOR_095:1:0]
WithP1Hand: SHD_079
WithP2Hand: SHD_120

## WHEN
- P1>Pass
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:1:CARDID:LAW_223
P1GROUNDARENAUNIT:1:READY

---

# FreePlayFromOppDeck_NoNonUnique_EntersExhausted
#// LAW_223 Rose Tico — "you control" reads the player Rose enters play UNDER, however she was played.
#// P1's LAW_215 Vermillion survives its attack and reveals the top of P2's deck (Rose — P1's own deck is
#// empty so the reveal source is automatic); P1 chooses itself and plays her for free. P1 controls no
#// non-unique unit — P2's own Imperial Dark Trooper must NOT count — so Rose enters P1's arena EXHAUSTED.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: LAW_215:1:0
WithP2GroundArena: SEC_080:1:0
WithP2Deck: LAW_223

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:You
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_223
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# FreePlayFromOppDeck_WithNonUnique_EntersReady
#// LAW_223 Rose Tico — the pair of the section above: same free play from the OPPONENT's deck via
#// LAW_215 Vermillion, but this time P1 controls the non-unique Battlefield Marine, so Rose (owned by P2,
#// controlled by P1) enters play READY.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: LAW_215:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP2Deck: LAW_223

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:You
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:LAW_223
P1GROUNDARENAUNIT:1:READY
